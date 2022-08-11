<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Maclof\Kubernetes\Client as KubernetesClient;
use Maclof\Kubernetes\Models\NamespaceModel as KubeNamespace;
use Maclof\Kubernetes\Models\SubnamespaceAnchor;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\GenericTranscriberInterface;
use Throwable;

use function array_pop;
use function explode;
use function implode;
use function strtolower;

/**
 * Exposing Transcriber to translate CompiledDeployment's namespace to Kubernetes namespace manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class NamespaceTranscriber implements GenericTranscriberInterface
{
    private static function convertToNamespace(string $namespace): KubeNamespace
    {
        $specs = [
            'metadata' => [
                'name' => $namespace,
                'labels' => [
                    'name' => $namespace,
                ],
            ]
        ];

        return new KubeNamespace($specs);
    }

    private static function convertToSubnamespace(string $namespace): SubnamespaceAnchor
    {
        $parts = explode('-', $namespace);
        $namespaceChild = array_pop($parts);
        $namespaceParent = implode('-', $parts);

        $specs = [
            'metadata' => [
                'name' => $namespaceChild,
                'namespace' => $namespaceParent,
                'labels' => [
                    'name' => $namespace,
                ],
            ],
        ];

        return new SubnamespaceAnchor($specs);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->forNamespace(
            static function (string $namespace, bool $hierarchicalNamespaces) use ($client, $promise) {
                $namespace = strtolower($namespace);

                try {
                    $namespaceRepository = $client->namespaces();
                    if (true === $hierarchicalNamespaces) {
                        $subnamespacesAnchorsRepository = $client->subnamespacesAnchors();
                    }

                    $result = null;
                    if (!$namespaceRepository->exists($namespace)) {
                        if (true === $hierarchicalNamespaces) {
                            $result = $subnamespacesAnchorsRepository->create(self::convertToSubnamespace($namespace));
                        } else {
                            $result = $namespaceRepository->create(self::convertToNamespace($namespace));
                        }
                    }

                    $promise->success($result);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
