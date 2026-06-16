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
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;

use function array_map;
use function iterator_to_array;
use function sprintf;

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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait PodsTranscriberTrait
{
    private const string SECRET_SUFFIX = '-secret';

    private const string MAP_SUFFIX = '-map';

    private const string VOLUME_SUFFIX = '-volume';

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

            return $image->getUrl() . ':' . $image->getTag();
        }

        return $container->getImage() . ':' . $version;
    }

    /**
     * Convert a container's variables to Compose `environment` entries; references to secrets and maps are
     * turned into `secrets:`/`configs:` mounts.
     *
     * @param array<string, mixed> $spec
     * @param array<string, mixed> $variables
     * @param callable(string): string $prefixer
     */
    private static function convertVariables(array &$spec, array $variables, callable $prefixer): void
    {
        /** @var list<string> $secrets */
        $secrets = $spec['secrets'] ?? [];
        /** @var list<string> $configs */
        $configs = $spec['configs'] ?? [];
        $environment = [];

        foreach ($variables as $name => $value) {
            if ($value instanceof SecretReference) {
                $secrets[] = $prefixer($value->getName() . self::SECRET_SUFFIX);

                continue;
            }

            if ($value instanceof MapReference) {
                $configs[] = $prefixer($value->getName() . self::MAP_SUFFIX);

                continue;
            }

            $environment[$name] = $value;
        }

        if (!empty($secrets)) {
            $spec['secrets'] = $secrets;
        }

        if (!empty($configs)) {
            $spec['configs'] = $configs;
        }

        if (!empty($environment)) {
            $spec['environment'] = $environment;
        }
    }

    /**
     * Map a container's volumes to Compose `volumes:`/`secrets:`/`configs:` mounts, using each volume's
     * declared mount path.
     *
     * @param array<string, mixed> $spec
     * @param callable(string): string $prefixer
     */
    private static function convertVolumes(array &$spec, Container $container, callable $prefixer): void
    {
        /** @var list<string> $volumes */
        $volumes = $spec['volumes'] ?? [];
        /** @var list<string> $secrets */
        $secrets = $spec['secrets'] ?? [];
        /** @var list<string> $configs */
        $configs = $spec['configs'] ?? [];

        foreach ($container->getVolumes() as $volume) {
            if ($volume instanceof PersistentVolumeInterface) {
                $volumes[] = sprintf(
                    '%s:%s',
                    $prefixer($volume->getName()),
                    $volume->getMountPath(),
                );

                continue;
            }

            if ($volume instanceof SecretVolume) {
                $secrets[] = $prefixer($volume->getSecretIdentifier() . self::SECRET_SUFFIX);

                continue;
            }

            if ($volume instanceof MapVolume) {
                $configs[] = $prefixer($volume->getMapIdentifier() . self::MAP_SUFFIX);

                continue;
            }

            $volumes[] = sprintf(
                '%s:%s',
                $prefixer($volume->getName() . self::VOLUME_SUFFIX),
                $volume->getMountPath(),
            );
        }

        if (!empty($volumes)) {
            $spec['volumes'] = $volumes;
        }

        if (!empty($secrets)) {
            $spec['secrets'] = $secrets;
        }

        if (!empty($configs)) {
            $spec['configs'] = $configs;
        }
    }

    /**
     * Map a PaaS HealthCheck to a Compose `healthcheck` block.
     *
     * @return array<string, mixed>
     */
    private static function convertHealthCheck(HealthCheck $healthCheck): array
    {
        $test = match ($healthCheck->getType()) {
            HealthCheckType::Command => array_map(
                static fn (string $part): string => $part,
                ['CMD', ...($healthCheck->getCommand() ?? [])],
            ),
            HealthCheckType::Tcp => [
                'CMD-SHELL',
                sprintf('nc -z localhost %d || exit 1', (int) $healthCheck->getPort()),
            ],
            HealthCheckType::Http => [
                'CMD-SHELL',
                sprintf(
                    'curl -fk %s://localhost:%d%s || exit 1',
                    true === $healthCheck->isSecure() ? 'https' : 'http',
                    (int) $healthCheck->getPort(),
                    (string) $healthCheck->getPath(),
                ),
            ],
        };

        return [
            'test' => $test,
            'start_period' => sprintf('%ds', $healthCheck->getInitialDelay()),
            'interval' => sprintf('%ds', $healthCheck->getPeriod()),
            'retries' => $healthCheck->getFailureThreshold(),
        ];
    }

    /**
     * Map a container's ResourceSet to a Compose `deploy.resources` block.
     *
     * @return array<string, mixed>
     */
    private static function convertResources(Container $container): array
    {
        $reservations = [];
        $limits = [];

        /** @var Resource $resource */
        foreach ($container->getResources() as $resource) {
            $reservations[$resource->getType()] = $resource->getRequire();
            $limits[$resource->getType()] = $resource->getLimit();
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
     * @return array<string, mixed>
     */
    private static function containerToService(
        Pod $pod,
        Container $container,
        array $images,
        callable $prefixer,
        string $networkName,
    ): array {
        $spec = [
            'image' => self::resolveImageUrl($container, $images),
            'networks' => [$networkName],
        ];

        if (!empty($ports = $container->getListen())) {
            $spec['expose'] = array_map(static fn (int $port): int => $port, $ports);
        }

        self::convertVariables($spec, $container->getVariables(), $prefixer);
        self::convertVolumes($spec, $container, $prefixer);

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

        return $spec;
    }

    /**
     * Build the Compose service(s) representing a pod. Returns a map of service name => Compose service spec.
     * For multi-container pods, the anchor service is named after the pod and each sidecar shares its
     * network namespace via `network_mode: "service:<anchor>"`.
     *
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param callable(string): string $prefixer
     * @return array<string, array<string, mixed>>
     */
    protected static function podToServices(
        Pod $pod,
        array $images,
        callable $prefixer,
        string $networkName,
    ): array {
        /** @var array<int, Container> $containers */
        $containers = iterator_to_array($pod, false);

        $services = [];
        $anchorName = $pod->getName();

        foreach ($containers as $index => $container) {
            if (0 === $index) {
                $services[$anchorName] = self::containerToService(
                    pod: $pod,
                    container: $container,
                    images: $images,
                    prefixer: $prefixer,
                    networkName: $networkName,
                );

                continue;
            }

            $sidecar = self::containerToService(
                pod: $pod,
                container: $container,
                images: $images,
                prefixer: $prefixer,
                networkName: $networkName,
            );

            unset($sidecar['networks'], $sidecar['expose']);
            $sidecar['network_mode'] = 'service:' . $anchorName;

            $services[$anchorName . '-' . $container->getName()] = $sidecar;
        }

        return $services;
    }
}
