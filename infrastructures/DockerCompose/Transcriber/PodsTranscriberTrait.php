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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod\RestartPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;

use function array_map;
use function array_unique;
use function array_values;
use function implode;
use function is_array;
use function iterator_to_array;
use function number_format;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_ends_with;
use function str_replace;
use function substr;

use const PHP_EOL;

/**
 * Trait factorising the shared Pod -> Compose service(s) logic used by the deployment transcribers of the
 * Docker Compose driver.
 *
 * A single-container pod becomes a single Compose service named after the pod (so `Service.podName`
 * resolves). A multi-container pod becomes an anchor service (named after the pod, holding the first
 * container) plus one sidecar service per additional container, each declared with
 * `network_mode: "service:<anchor>"` so they share the pod's localhost and port space, replicating the
 * Kubernetes pod network sharing.
 *
 * Environment variables read from `map` secrets / maps (`from-secrets`, `import-secrets`, `from-maps`,
 * `import-maps`) are resolved from the pre-scanned values into a per-container env file
 * (`secrets/<pod>-<container>.env`, pushed with mode 0600 and referenced by `env_file:`) so the compose
 * file and the job History never carry a secret value. Secret/map volumes are mounted one file per key
 * under their declared mount path, like Kubernetes.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait PodsTranscriberTrait
{
    use ValuesCollectorTrait;

    private const string SECRET_SUFFIX = '-secret';

    private const string MAP_SUFFIX = '-map';

    private const string VOLUME_SUFFIX = '-volume';

    private const string ENV_FILE_DIR = 'secrets/';

    /**
     * Strip a leading `scheme://` (e.g. the registry API URL `https://foo.bar`) from an image reference:
     * a Docker image reference is a `host[:port]/name`, never a URL.
     */
    private static function stripScheme(string $url): string
    {
        return (string) preg_replace('#^[a-z][a-z0-9+.\-]*://#i', '', $url);
    }

    /**
     * Resolve the fully qualified image reference (`url:tag`) of a container, falling back to its declared
     * image/version when the image was not built by the build stage.
     *
     * @param array<string, array<string, Image>>|Image[][] $images
     */
    private static function resolveImageUrl(Container $container, array $images): string
    {
        $version = (string) $container->getVersion();

        if (isset($images[$container->getImage()][$version])) {
            $image = $images[$container->getImage()][$version];

            return self::stripScheme($image->getUrl()) . ':' . $image->getTag();
        }

        return $container->getImage() . ':' . $version;
    }

    /**
     * Quote a value for a Compose env file (double-quoted form): backslashes, double quotes and line breaks
     * are escaped, and `$` is doubled so Compose does not interpolate it.
     */
    private static function quoteEnvValue(string $value): string
    {
        $value = str_replace(
            ['\\', '"', "\r", "\n", '$'],
            ['\\\\', '\\"', '\\r', '\\n', '$$'],
            $value,
        );

        return '"' . $value . '"';
    }

    /**
     * @param array<string, array<string, string>> $values pre-scanned values, keyed by raw resource name
     * @return array<string, string> variable name => value
     */
    private static function resolveReference(
        SecretReference|MapReference $reference,
        string $variableName,
        array $values,
        string $kind,
    ): array {
        $name = $reference->getName();
        if (!isset($values[$name])) {
            throw new InvalidConfigurationException(
                "The $kind `$name` referenced by the variable `$variableName` is not a `map` $kind of this "
                . 'deployment: only inline `map` values are available on a Docker Compose host',
            );
        }

        if ($reference->isImportAll()) {
            return $values[$name];
        }

        //A missing key is not a deploy-time error (Kubernetes resolves it lazily and refuses to start the
        //container): the variable is defined, empty.
        $key = (string) $reference->getKey();

        return [$variableName => $values[$name][$key] ?? ''];
    }

    /**
     * Convert a container's variables to Compose `environment` entries; references to secrets and maps are
     * resolved into a per-container env file referenced by `env_file:`.
     *
     * @param array<string, mixed> $spec
     * @param array<string, mixed> $variables
     * @param array<string, array<string, string>> $secrets
     * @param array<string, array<string, string>> $maps
     */
    private static function convertVariables(
        array &$spec,
        array $variables,
        array $secrets,
        array $maps,
        string $envFileName,
        AccumulatorInterface $accumulator,
    ): void {
        $environment = [];
        $fromFiles = [];

        foreach ($variables as $name => $value) {
            if ($value instanceof SecretReference) {
                $fromFiles += self::resolveReference($value, (string) $name, $secrets, 'secret');

                continue;
            }

            if ($value instanceof MapReference) {
                $fromFiles += self::resolveReference($value, (string) $name, $maps, 'map');

                continue;
            }

            $environment[$name] = $value;
        }

        if (!empty($fromFiles)) {
            $lines = [];
            foreach ($fromFiles as $name => $value) {
                $lines[] = $name . '=' . self::quoteEnvValue($value);
            }

            $path = self::ENV_FILE_DIR . $envFileName;
            $accumulator->addFile($path, implode(PHP_EOL, $lines) . PHP_EOL);
            $spec['env_file'] = ['./' . $path];
        }

        if (!empty($environment)) {
            $spec['environment'] = $environment;
        }
    }

    /**
     * Map a container's volumes to Compose `volumes:`/`secrets:`/`configs:` mounts, using each volume's
     * declared mount path. Secret and map volumes are mounted one file per key (`<mountPath>/<key>`) from
     * the per-key Compose secrets/configs declared by the Secret/ConfigMap transcribers.
     *
     * Populated/embedded volumes carry pre-existing data baked into a per-volume OCI image. They are
     * reproduced like the Kubernetes initContainer pattern: a one-shot init service runs that image (its
     * own CMD copies the baked data to `$MOUNT_PATH`) to populate a named volume, which the main service
     * mounts read-only after the init `service_completed_successfully`. The registry-qualified image URL
     * is taken from the deployment-level volume (`$deploymentVolumes["<container>_<key>"]`), since the
     * container's own volume instance has no registry.
     *
     * @param array<string, mixed> $spec
     * @param callable(string): string $prefixer
     * @param array<array-key, mixed> $deploymentVolumes deployment volumes keyed `<container>_<key>`
     * @param array<string, array<string, string>> $secrets
     * @param array<string, array<string, string>> $maps
     */
    private static function convertVolumes(
        array &$spec,
        Container $container,
        callable $prefixer,
        AccumulatorInterface $accumulator,
        array $deploymentVolumes,
        array $secrets,
        array $maps,
    ): void {
        /** @var list<string> $volumes */
        $volumes = $spec['volumes'] ?? [];
        /** @var list<array<string, string>> $secretsMounts */
        $secretsMounts = [];
        /** @var list<array<string, string>> $configsMounts */
        $configsMounts = [];
        /** @var array<string, array<string, string>> $dependsOn */
        $dependsOn = $spec['depends_on'] ?? [];

        foreach ($container->getVolumes() as $volumeKey => $volume) {
            if ($volume instanceof PersistentVolumeInterface) {
                $volumes[] = $prefixer($volume->getName()) . ':' . $volume->getMountPath();

                continue;
            }

            if ($volume instanceof SecretVolume) {
                $identifier = $volume->getSecretIdentifier();
                if (!isset($secrets[$identifier])) {
                    throw new InvalidConfigurationException(
                        "The secret `$identifier` mounted by the volume `{$volume->getName()}` is not a `map` "
                        . 'secret of this deployment: only inline `map` values are available on a Docker Compose host',
                    );
                }

                $baseName = $prefixer($identifier . self::SECRET_SUFFIX);
                foreach ($secrets[$identifier] as $key => $value) {
                    $key = self::sanitizeKey($key);
                    $secretsMounts[] = [
                        'source' => $baseName . '-' . $key,
                        'target' => rtrim($volume->getMountPath(), '/') . '/' . $key,
                    ];
                }

                continue;
            }

            if ($volume instanceof MapVolume) {
                $identifier = $volume->getMapIdentifier();
                if (!isset($maps[$identifier])) {
                    throw new InvalidConfigurationException(
                        "The map `$identifier` mounted by the volume `{$volume->getName()}` does not exist in "
                        . 'this deployment',
                    );
                }

                $baseName = $prefixer($identifier . self::MAP_SUFFIX);
                foreach ($maps[$identifier] as $key => $value) {
                    $key = self::sanitizeKey($key);
                    $configsMounts[] = [
                        'source' => $baseName . '-' . $key,
                        'target' => rtrim($volume->getMountPath(), '/') . '/' . $key,
                    ];
                }

                continue;
            }

            //Populated/embedded volume: populate a named volume from its image through an init service.
            $volumeName = (string) $prefixer($volume->getName() . self::VOLUME_SUFFIX);
            $mountPath = $volume->getMountPath();

            //The registry-qualified image lives on the deployment-level volume; the container's own
            //instance has no registry. Fall back to the container volume if the lookup misses.
            $urlSource = $deploymentVolumes[$container->getName() . '_' . $volumeKey] ?? $volume;
            $imageUrl = '';
            if ($urlSource instanceof Volume) {
                $imageUrl = self::stripScheme($urlSource->getUrl());
            } elseif ($volume instanceof Volume) {
                $imageUrl = self::stripScheme($volume->getUrl());
            }

            $accumulator->addVolume($volumeName, ['driver' => 'local']);

            $initServiceName = $volumeName . '-init';
            $accumulator->addService($initServiceName, [
                'image' => $imageUrl,
                'environment' => ['MOUNT_PATH' => $mountPath],
                'volumes' => [$volumeName . ':' . $mountPath],
                'network_mode' => 'none',
                'restart' => 'no',
            ]);

            //Main service mounts the populated volume read-only, after the init service has populated it.
            $volumes[] = $volumeName . ':' . $mountPath . ':ro';
            $dependsOn[$initServiceName] = ['condition' => 'service_completed_successfully'];
        }

        if (!empty($volumes)) {
            $spec['volumes'] = array_values(array_unique($volumes));
        }

        if (!empty($secretsMounts)) {
            $spec['secrets'] = $secretsMounts;
        }

        if (!empty($configsMounts)) {
            $spec['configs'] = $configsMounts;
        }

        if (!empty($dependsOn)) {
            $spec['depends_on'] = $dependsOn;
        }
    }

    /**
     * Map a PaaS HealthCheck to a Compose `healthcheck` block.
     *
     * @return array<string, mixed>
     */
    private static function convertHealthCheck(HealthCheck $healthCheck): array
    {
        $port = (int) $healthCheck->getPort();

        if (true === $healthCheck->isSecure()) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }

        $test = match ($healthCheck->getType()) {
            HealthCheckType::Command => [
                'CMD-SHELL',
                implode(' ', $healthCheck->getCommand() ?? []),
            ],
            HealthCheckType::Tcp => [
                'CMD-SHELL',
                'nc -z localhost ' . $port . ' || exit 1',
            ],
            HealthCheckType::Http => [
                'CMD-SHELL',
                'curl -fk ' . $scheme . '://localhost:' . $port . (string) $healthCheck->getPath()
                    . ' || exit 1',
            ],
        };

        return [
            'test' => $test,
            'start_period' => $healthCheck->getInitialDelay() . 's',
            'interval' => $healthCheck->getPeriod() . 's',
            'timeout' => '5s',
            'retries' => $healthCheck->getFailureThreshold(),
        ];
    }

    /**
     * Convert a Kubernetes CPU quantity (`500m`, `0.5`, `2`) to the decimal string Compose expects for `cpus`.
     */
    private static function convertCpu(string $quantity): string
    {
        if (str_ends_with($quantity, 'm')) {
            $value = ((float) substr($quantity, 0, -1)) / 1000;

            return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
        }

        return $quantity;
    }

    /**
     * Convert a Kubernetes memory quantity (`64Mi`, `1Gi`, `128M`) to a Compose byte value: Compose (Docker)
     * only knows the `b`/`k`/`m`/`g` suffixes (binary), so the `i` of the binary suffixes is dropped.
     */
    private static function convertMemory(string $quantity): string
    {
        if (1 === preg_match('#^(\d+(?:\.\d+)?)\s*([EPTGMk])i$#', $quantity, $matches)) {
            return $matches[1] . $matches[2];
        }

        return $quantity;
    }

    /**
     * Map a container's ResourceSet to a Compose `deploy.resources` block (`cpus`/`memory` only, the other
     * Kubernetes resource types have no Compose equivalent and are skipped).
     *
     * @return array<string, mixed>
     */
    private static function convertResources(Container $container): array
    {
        $reservations = [];
        $limits = [];

        /** @var Resource $resource */
        foreach ($container->getResources() as $resource) {
            [$key, $converter] = match ($resource->getType()) {
                'cpu' => ['cpus', self::convertCpu(...)],
                'memory' => ['memory', self::convertMemory(...)],
                default => [null, null],
            };

            if (null === $key || null === $converter) {
                continue;
            }

            if ('' !== ($require = $resource->getRequire())) {
                $reservations[$key] = $converter($require);
            }

            if ('' !== ($limit = $resource->getLimit())) {
                $limits[$key] = $converter($limit);
            }
        }

        $resources = [];
        if (!empty($reservations)) {
            $resources['reservations'] = $reservations;
        }

        if (!empty($limits)) {
            $resources['limits'] = $limits;
        }

        return $resources;
    }

    /**
     * Map a Pod RestartPolicy to the Compose `restart` value.
     */
    private static function convertRestartPolicy(?RestartPolicy $restartPolicy): ?string
    {
        if (null === $restartPolicy) {
            return null;
        }

        return match ($restartPolicy) {
            RestartPolicy::Always => 'always',
            RestartPolicy::OnFailure => 'on-failure',
            RestartPolicy::Never => 'no',
        };
    }

    /**
     * Build the Compose service spec for a single container of a pod.
     *
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param callable(string): string $prefixer
     * @param array<array-key, mixed> $deploymentVolumes deployment volumes keyed `<container>_<key>`
     * @param array<string, array<string, string>> $secrets
     * @param array<string, array<string, string>> $maps
     * @return array<string, mixed>
     */
    private static function containerToService(
        Pod $pod,
        Container $container,
        array $images,
        callable $prefixer,
        string $networkName,
        AccumulatorInterface $accumulator,
        array $deploymentVolumes,
        array $secrets,
        array $maps,
        string $envFileName,
    ): array {
        $spec = [
            'image' => self::resolveImageUrl($container, $images),
            'networks' => [$networkName],
        ];

        if (!empty($ports = $container->getListen())) {
            $spec['expose'] = array_map(static fn (int $port): int => $port, $ports);
        }

        self::convertVariables($spec, $container->getVariables(), $secrets, $maps, $envFileName, $accumulator);
        self::convertVolumes($spec, $container, $prefixer, $accumulator, $deploymentVolumes, $secrets, $maps);

        if (null !== ($healthCheck = $container->getHealthCheck())) {
            $spec['healthcheck'] = self::convertHealthCheck($healthCheck);
        }

        $deploy = [];
        if (!empty($resources = self::convertResources($container))) {
            $deploy['resources'] = $resources;
        }

        if ($pod->getReplicas() > 1) {
            $deploy['replicas'] = $pod->getReplicas();
        }

        if (!empty($deploy)) {
            $spec['deploy'] = $deploy;
        }

        if (null !== ($restart = self::convertRestartPolicy($pod->getRestartPolicy()))) {
            $spec['restart'] = $restart;
        }

        //Pod fsGroup -> `group_add` best-effort: Compose has no exact fsGroup equivalent (Kubernetes
        //recursively chowns mounted volumes to the fsGroup), so the pod's fsGroup is added as a
        //supplementary group of the container process. This is partial support: it does not chown existing
        //volume contents; volumes must already be group-readable/writable by that GID on the host.
        if (null !== ($fsGroup = $pod->getFsGroup())) {
            $spec['group_add'] = [(string) $fsGroup];
        }

        return $spec;
    }

    /**
     * Build the Compose service(s) representing a pod. Returns a map of service name => Compose service spec.
     * For multi-container pods, the anchor service is named after the pod and each sidecar shares its
     * network namespace via `network_mode: "service:<anchor>"` (so the sidecars are never replicated on
     * their own: `deploy.replicas` is only kept on the anchor).
     *
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param callable(string): string $prefixer
     * @param array<array-key, mixed> $deploymentVolumes deployment volumes keyed `<container>_<key>`
     * @param array<string, array<string, string>> $secrets pre-scanned `map` secrets values
     * @param array<string, array<string, string>> $maps pre-scanned maps values
     * @param string $envFilePrefix prefix of the per-container env file names (to isolate job pods)
     * @return array<string, array<string, mixed>>
     */
    protected static function podToServices(
        Pod $pod,
        array $images,
        callable $prefixer,
        string $networkName,
        AccumulatorInterface $accumulator,
        array $deploymentVolumes = [],
        array $secrets = [],
        array $maps = [],
        string $envFilePrefix = '',
    ): array {
        /** @var array<int, Container> $containers */
        $containers = iterator_to_array($pod, false);

        $services = [];
        $anchorName = $pod->getName();

        foreach ($containers as $index => $container) {
            $envFileName = $envFilePrefix . $anchorName . '-' . $container->getName() . '.env';

            if (0 === $index) {
                $services[$anchorName] = self::containerToService(
                    pod: $pod,
                    container: $container,
                    images: $images,
                    prefixer: $prefixer,
                    networkName: $networkName,
                    accumulator: $accumulator,
                    deploymentVolumes: $deploymentVolumes,
                    secrets: $secrets,
                    maps: $maps,
                    envFileName: $envFileName,
                );

                continue;
            }

            $sidecar = self::containerToService(
                pod: $pod,
                container: $container,
                images: $images,
                prefixer: $prefixer,
                networkName: $networkName,
                accumulator: $accumulator,
                deploymentVolumes: $deploymentVolumes,
                secrets: $secrets,
                maps: $maps,
                envFileName: $envFileName,
            );

            unset($sidecar['networks'], $sidecar['expose']);
            if (is_array($sidecar['deploy'] ?? null)) {
                unset($sidecar['deploy']['replicas']);
                if (empty($sidecar['deploy'])) {
                    unset($sidecar['deploy']);
                }
            }

            $sidecar['network_mode'] = 'service:' . $anchorName;

            $services[$anchorName . '-' . $container->getName()] = $sidecar;
        }

        return $services;
    }
}
