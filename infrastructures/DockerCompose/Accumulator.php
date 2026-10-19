<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\DockerCompose;

use Generator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\FileToCopy;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\InlineContent;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\JobRunSettings;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\MountedFile;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function basename;
use function escapeshellarg;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function preg_replace;
use function str_starts_with;
use function strlen;
use function strpbrk;
use function substr;
use function trim;

/**
 * Mutable accumulator (builder) used by the Docker Compose driver's transcribers to collect the full Compose
 * Specification file, the Traefik dynamic configuration, the files to push to the host and the per-ingress
 * TLS certificates, before the driver serializes them and runs the Ansible playbooks.
 *
 * Services reach each other on a per-project, dedicated network (`<project>-<network>`, `driver: bridge`,
 * optionally `internal: true`) declared in the Compose file with an explicit `name:`. The deploy playbook
 * connects Traefik to that resolved network name so it can route ingress traffic to the services.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class Accumulator implements AccumulatorInterface
{
    private const string CRON_ENTRY_PREFIX = 'east-paas:';

    /**
     * Command of a crontab entry, with a PATH: the one of cron is minimal
     */
    private const string CRON_COMMAND = 'PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh';

    private const string NETWORK_SERVICE_PREFIX = 'service:';

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $services = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $volumes = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $configs = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $secrets = [];

    /**
     * @var array<string, array<string, array<string, array<string, mixed>>>>
     */
    private array $traefik = [
        'http' => ['routers' => [], 'services' => [], 'serversTransports' => []],
        'tcp' => ['routers' => [], 'services' => []],
        'udp' => ['routers' => [], 'services' => []],
    ];

    /**
     * @var array<int, array{certFile: string, keyFile: string}>
     */
    private array $certificates = [];

    private ?string $certResolver = null;

    /**
     * @var array<string, string>
     */
    private array $files = [];

    /**
     * @var array<int, string>
     */
    private array $warnings = [];

    /**
     * @param string $traefikCertsMountDir directory where the pushed TLS cert/key files are visible from the
     *        Traefik process (the path referenced by the dynamic configuration `tls.certificates`)
     */
    public function __construct(
        private readonly string $projectName,
        private readonly string $dedicatedNetworkName = 'private',
        private readonly string $networkDriver = 'bridge',
        private readonly bool $networkInternal = false,
        private readonly string $traefikCertsMountDir = '/etc/traefik/certs',
    ) {
    }

    public function getProjectName(): string
    {
        return $this->projectName;
    }

    public function getNetworkName(): string
    {
        return $this->projectName . '-' . $this->dedicatedNetworkName;
    }

    public function addService(string $name, array $spec): AccumulatorInterface
    {
        $this->services[$name] = $spec;

        return $this;
    }

    public function publishPorts(string $name, array $ports): AccumulatorInterface
    {
        if (empty($ports)) {
            return $this;
        }

        $existing = [];
        if (isset($this->services[$name]['ports']) && is_array($this->services[$name]['ports'])) {
            $existing = $this->services[$name]['ports'];
        }

        $this->services[$name]['ports'] = array_values(array_merge($existing, $ports));

        return $this;
    }

    public function addNetworkAlias(string $name, string $alias): AccumulatorInterface
    {
        if (!isset($this->services[$name])) {
            return $this;
        }

        //A sidecar sharing its anchor's network namespace (`network_mode: service:<anchor>`) has no
        //`networks:` of its own, the alias must be declared on the anchor.
        if (!isset($this->services[$name]['networks'])) {
            return $this;
        }

        $networks = $this->services[$name]['networks'];
        if (!is_array($networks)) {
            return $this;
        }

        $normalized = [];
        foreach ($networks as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $normalized[$value] = [];

                continue;
            }

            if (!is_array($value)) {
                $normalized[(string) $key] = [];

                continue;
            }

            $normalized[(string) $key] = $value;
        }

        $networkName = $this->getNetworkName();
        /** @var array<int, string> $aliases */
        $aliases = [];
        if (is_array($normalized[$networkName]['aliases'] ?? null)) {
            foreach ($normalized[$networkName]['aliases'] as $existing) {
                if (is_string($existing)) {
                    $aliases[] = $existing;
                }
            }
        }

        if ($alias !== $name) {
            $aliases[] = $alias;
        }

        if (empty($aliases)) {
            //Nothing to declare, keep the short `networks:` list form
            return $this;
        }

        $normalized[$networkName] ??= [];
        $normalized[$networkName]['aliases'] = array_values(array_unique($aliases));
        $this->services[$name]['networks'] = $normalized;

        return $this;
    }

    public function addVolume(string $name, array $spec): AccumulatorInterface
    {
        $this->volumes[$name] = $spec;

        return $this;
    }

    public function addConfig(string $name, MountedFile|InlineContent $definition): AccumulatorInterface
    {
        if ($definition instanceof MountedFile) {
            $this->configs[$name] = ['file' => './' . $definition->path];
            $this->addFile($definition->path, $definition->content);

            return $this;
        }

        $this->configs[$name] = ['content' => $definition->content];

        return $this;
    }

    public function addSecret(string $name, MountedFile|InlineContent $definition): AccumulatorInterface
    {
        //The Compose Specification has no inline form for a secret: an inline value is materialised to a
        //file under secrets/ exactly like a MountedFile.
        if ($definition instanceof InlineContent) {
            $definition = new MountedFile('secrets/' . $name, $definition->content);
        }

        $this->secrets[$name] = ['file' => './' . $definition->path];
        $this->addFile($definition->path, $definition->content);

        return $this;
    }

    public function addTraefikRouter(string $kind, string $name, array $spec): AccumulatorInterface
    {
        $this->traefik[$kind]['routers'][$name] = $spec;

        return $this;
    }

    public function addTraefikService(string $kind, string $name, array $spec): AccumulatorInterface
    {
        $this->traefik[$kind]['services'][$name] = $spec;

        return $this;
    }

    public function addTraefikServersTransport(string $name, array $spec): AccumulatorInterface
    {
        $this->traefik['http']['serversTransports'][$name] = $spec;

        return $this;
    }

    public function addTlsCertificate(string $certFile, string $keyFile): AccumulatorInterface
    {
        $this->certificates[] = [
            'certFile' => $certFile,
            'keyFile' => $keyFile,
        ];

        return $this;
    }

    public function setCertResolver(string $name): AccumulatorInterface
    {
        $this->certResolver = $name;

        return $this;
    }

    public function addFile(string $relativePath, string $content): AccumulatorInterface
    {
        $this->files[$relativePath] = $content;

        return $this;
    }

    public function getComposeFile(): array
    {
        $compose = [];

        if (!empty($this->services)) {
            $compose['services'] = $this->services;
            //The dedicated network name is pinned with an explicit `name:` to the per-project value
            //(`<project>-<network>`) so Compose does not prefix it again with the project name; that resolved
            //name is what the deploy playbook connects Traefik to and what Traefik's `loadBalancer` URLs use
            //(`<service>.<network>`) to stay unambiguous when Traefik is connected to several projects.
            //When `internal` is set, containers are reachable only through Traefik and have no egress.
            $networkName = $this->getNetworkName();
            $network = [
                'name' => $networkName,
                'driver' => $this->networkDriver,
            ];

            if ($this->networkInternal) {
                $network['internal'] = true;
            }

            $compose['networks'] = [$networkName => $network];
        }

        if (!empty($this->volumes)) {
            $compose['volumes'] = $this->volumes;
        }

        if (!empty($this->configs)) {
            $compose['configs'] = $this->configs;
        }

        if (!empty($this->secrets)) {
            $compose['secrets'] = $this->secrets;
        }

        return $compose;
    }

    /**
     * Yield only the non-empty Traefik sections (`http`/`tcp`/`udp` => `routers`/`services`), skipping every
     * empty kind and empty section so the serialized dynamic configuration carries no empty containers.
     *
     * @return Generator<string, array<string, array<string, mixed>>>
     */
    private function nonEmptyTraefikSections(): Generator
    {
        foreach ($this->traefik as $kind => $sections) {
            $kindConfig = [];
            foreach ($sections as $section => $entries) {
                if (empty($entries)) {
                    continue;
                }

                $kindConfig[$section] = $entries;
            }

            if (empty($kindConfig)) {
                continue;
            }

            yield $kind => $kindConfig;
        }
    }

    public function getTraefikConfig(): array
    {
        $config = [];

        foreach ($this->nonEmptyTraefikSections() as $kind => $kindConfig) {
            $config[$kind] = $kindConfig;
        }

        if (!empty($this->certificates)) {
            //The files are pushed by the expose playbook into the host certs directory under their base
            //name; Traefik must see that directory at `$traefikCertsMountDir`.
            $tls = [];
            foreach ($this->certificates as $certificate) {
                $tls['certificates'][] = [
                    'certFile' => $this->traefikCertsMountDir . '/' . basename($certificate['certFile']),
                    'keyFile' => $this->traefikCertsMountDir . '/' . basename($certificate['keyFile']),
                ];
            }

            $config['tls'] = $tls;
        }

        if (null !== $this->certResolver) {
            $config['tls']['certResolver'] = $this->certResolver;
        }

        return $config;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function addWarning(string $message): AccumulatorInterface
    {
        $this->warnings[] = $message;

        return $this;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getFilesToCopy(): array
    {
        $entries = [];
        foreach ($this->files as $relativePath => $content) {
            if (str_starts_with($relativePath, 'secrets/')) {
                $mode = '0600';
            } else {
                $mode = '0640';
            }

            $entries[] = new FileToCopy(
                src: $relativePath,
                dest: $relativePath,
                mode: $mode,
            );
        }

        return $entries;
    }

    public function getCertificatesToCopy(): array
    {
        $entries = [];
        foreach ($this->certificates as $certificate) {
            $entries[] = new FileToCopy(
                src: $certificate['certFile'],
                dest: basename($certificate['certFile']),
            );
            $entries[] = new FileToCopy(
                src: $certificate['keyFile'],
                dest: basename($certificate['keyFile']),
            );
        }

        return $entries;
    }

    public function getResetVolumes(): array
    {
        $names = [];
        foreach ($this->volumes as $name => $spec) {
            if (!empty($spec['x-paas-reset'])) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param array<string, mixed> $spec
     */
    private static function hasProfile(array $spec, string $profile): bool
    {
        $profiles = $spec['profiles'] ?? [];

        return is_array($profiles) && in_array($profile, $profiles, true);
    }

    /**
     * @param array<string, mixed> $spec
     * @return array<mixed>
     */
    private static function jobMeta(array $spec): array
    {
        $meta = $spec['x-paas-job'] ?? [];
        if (!is_array($meta)) {
            return [];
        }

        return $meta;
    }

    /**
     * @param array<mixed> $meta
     */
    private static function stringMeta(array $meta, string $key): string
    {
        $value = $meta[$key] ?? '';
        if (!is_string($value)) {
            return '';
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $spec
     */
    private static function networkService(array $spec): string
    {
        $mode = $spec['network_mode'] ?? null;
        if (!is_string($mode) || !str_starts_with($mode, self::NETWORK_SERVICE_PREFIX)) {
            return '';
        }

        return substr($mode, strlen(self::NETWORK_SERVICE_PREFIX));
    }

    /**
     * Pods of a profile, keyed by their anchor: the anchor first, then its sidecars in their order of declaration
     *
     * @return array<string, list<string>>
     */
    private function podsOfProfile(string $profile): array
    {
        $targets = [];
        foreach ($this->services as $name => $spec) {
            if (self::hasProfile($spec, $profile)) {
                $targets[$name] = self::networkService($spec);
            }
        }

        $pods = [];
        foreach ($targets as $name => $target) {
            if ($name === $target || !isset($targets[$target])) {
                $pods[$name] = [$name];
            }
        }

        foreach ($targets as $name => $target) {
            if (!isset($pods[$name]) && isset($pods[$target])) {
                $pods[$target][] = $name;
            }
        }

        return $pods;
    }

    public function getJobsToRun(): array
    {
        $runs = [];
        foreach ($this->podsOfProfile('jobs') as $name => $services) {
            $settings = JobRunSettings::fromMeta(self::jobMeta($this->services[$name]));

            //Each completion is a separate, sequential run of the pod in the playbook (Compose has no native Job
            //object; `parallel` is not supported and runs are sequential).
            for ($run = 1; $run <= $settings->completions; $run++) {
                $runs[] = [
                    'service' => $name,
                    'services' => $services,
                    'run' => $run,
                    'completions' => $settings->completions,
                    'timeout' => $settings->timeLimit,
                    'ok_codes' => $settings->okCodes,
                ];
            }
        }

        return $runs;
    }

    public function getScheduledJobs(): array
    {
        $entries = [];
        foreach ($this->podsOfProfile(self::SCHEDULED_PROFILE) as $name => $services) {
            $schedule = $this->services[$name]['x-paas-schedule'] ?? null;
            if (!is_array($schedule)) {
                continue;
            }

            $arguments = [
                $name,
                //Informative, for an operator reading the crontab
                (string) preg_replace('#\s+#', ' ', trim(self::stringMeta($schedule, 'schedule'))),
            ];
            $job = self::CRON_COMMAND . ' ' . implode(' ', array_map(escapeshellarg(...), $arguments))
                . ' >>' . escapeshellarg('jobs/' . $name . '/events.log') . ' 2>&1';

            //A `%` is a newline for cron (the quotes do not protect it) and a line break ends the entry
            if (false !== strpbrk($job, "%\r\n")) {
                throw new InvalidConfigurationException(
                    "The scheduled job service `$name` can not be installed in a crontab: its entry would contain a "
                    . '`%` or a line break',
                );
            }

            $settings = JobRunSettings::fromMeta(self::jobMeta($this->services[$name]));

            $entries[] = [
                'name' => self::CRON_ENTRY_PREFIX . $this->projectName . ':' . $name,
                'service' => $name,
                'services' => $services,
                //The deploy playbook checks `timeout` on the host when a job has a time limit
                'timeout' => $settings->timeLimit,
                'disabled' => true === ($schedule['suspend'] ?? false),
                'job' => $job,
            ];
        }

        return $entries;
    }
}
