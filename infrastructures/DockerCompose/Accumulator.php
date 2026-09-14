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
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\FileToCopy;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\InlineContent;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\MountedFile;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function basename;
use function in_array;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function max;
use function str_starts_with;

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

            $normalized[(string) $key] = is_array($value) ? $value : [];
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

    public function getJobsToRun(): array
    {
        $runs = [];
        foreach ($this->services as $name => $spec) {
            $profiles = $spec['profiles'] ?? [];
            if (!is_array($profiles) || !in_array('jobs', $profiles, true)) {
                continue;
            }

            $meta = $spec['x-paas-job'] ?? [];
            if (!is_array($meta)) {
                $meta = [];
            }

            $completions = 1;
            if (is_numeric($meta['completions'] ?? null)) {
                $completions = max(1, (int) $meta['completions']);
            }

            $timeout = 0;
            if (is_numeric($meta['time_limit'] ?? null)) {
                $timeout = (int) $meta['time_limit'];
            }

            /** @var array<int, int> $okCodes */
            $okCodes = $meta['success_exit_codes'] ?? [];
            if (!is_array($okCodes) || empty($okCodes)) {
                $okCodes = [0];
            }

            //Each completion is a separate, sequential `docker compose run` in the playbook (Compose has no
            //native Job object; `parallel` is not supported and runs are sequential).
            for ($run = 1; $run <= $completions; $run++) {
                $runs[] = [
                    'service' => $name,
                    'run' => $run,
                    'completions' => $completions,
                    'timeout' => $timeout,
                    'ok_codes' => array_values(array_unique(array_map('intval', $okCodes))),
                ];
            }
        }

        return $runs;
    }
}
