<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\SubnamespaceAnchor;
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
 * "Exposing transcriber" to translate CompiledDeployment's namespace to Kubernetes namespace manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class NamespaceTranscriber implements GenericTranscriberInterface
{
    use CommonTrait;

    /**
     * @param array<int, string> $parts
     * @return array<string, mixed>
     */
    protected static function writeSpec(array $parts, string $namespace): array
    {
        $namespaceChild = array_pop($parts);
        $namespaceParent = implode('-', $parts);

        return [
            'metadata' => [
                'name' => $namespaceChild,
                'namespace' => $namespaceParent,
                'labels' => [
                    'name' => $namespace,
                ],
            ],
        ];
    }

    /**
     * @param array<int, string> $parts
     */
    private static function convertToSubnamespace(array $parts, string $namespace): SubnamespaceAnchor
    {
        return new SubnamespaceAnchor(
            static::writeSpec($parts, $namespace)
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->forNamespace(
            static function (string $namespace, bool $hierarchicalNamespaces) use ($client, $promise): void {
                $namespace = strtolower($namespace);
                $parts = explode('-', $namespace);

                if (false === $hierarchicalNamespaces || 1 === count($parts)) {
                    $promise->success([]);

                    return;
                }

                try {
                    $subnamespacesAnchorsRepository = $client->subnamespacesAnchors();

                    $result = $subnamespacesAnchorsRepository->apply(
                        self::convertToSubnamespace($parts, $namespace)
                    );

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
