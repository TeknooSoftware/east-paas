<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DriverAwareInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\GenericTranscriberInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\SubnamespaceAnchor;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Exposing transcriber" to translate CompiledDeployment's namespace to Kubernetes namespace manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class NamespaceTranscriber implements GenericTranscriberInterface, DriverAwareInterface
{
    use CommonTrait;

    private ?Driver $driver = null;

    public function setDriver(Driver $driver): DriverAwareInterface
    {
        $that = clone $this;
        $that->driver = $driver;

        return $that;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function writeSpec(string $namespace, string $projectName, string $finalNs): array
    {
        return [
            'metadata' => [
                'name' => $projectName,
                'namespace' => $namespace,
                'labels' => [
                    'name' => $finalNs,
                ],
            ],
        ];
    }

    private static function convertToSubnamespace(
        string $namespace,
        string $projectName,
        string $finalNs,
    ): SubnamespaceAnchor {
        return new SubnamespaceAnchor(
            static::writeSpec(
                namespace: $namespace,
                projectName: $projectName,
                finalNs: $finalNs,
            )
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
        bool $useHierarchicalNamespaces,
    ): TranscriberInterface {
        if (false === $useHierarchicalNamespaces) {
            $promise->success([]);

            return $this;
        }

        $driver = $this->driver;

        $compiledDeployment->withJobSettings(
            static function (
                float $version,
                string $prefix,
                string $projectName,
            ) use (
                $namespace,
                $client,
                $promise,
                $driver,
            ): void {
                try {
                    $client->setNamespace($namespace);
                    $subnamespacesAnchorsRepository = $client->subnamespacesAnchors();

                    $finalNs = $namespace . '-' . $projectName;
                    $result = $subnamespacesAnchorsRepository->apply(
                        self::convertToSubnamespace(
                            $namespace,
                            $projectName,
                            $finalNs,
                        )
                    );

                    $client->setNamespace($finalNs);
                    $driver?->updateNamespace($finalNs);

                    $result = self::cleanResult($result);

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
