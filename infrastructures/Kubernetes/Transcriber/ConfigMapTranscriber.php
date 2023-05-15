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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\ConfigMap as KubeConfigMap;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

/**
 * "Deployment transcriber" to translate CompiledDeployment's map to Kubernetes ConfigMaps manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConfigMapTranscriber implements DeploymentInterface
{
    use CommonTrait;

    private const NAME_SUFFIX = '-map';

    /**
     * @return array<string, mixed>
     */
    protected static function writeSpec(Map $configMap, string $namespace, callable $prefixer): array
    {
        return [
            'metadata' => [
                'name' => $prefixer($configMap->getName() . self::NAME_SUFFIX),
                'namespace' => $namespace,
                'labels' => [
                    'name' => $prefixer($configMap->getName()),
                ],
            ],
            'data' => $configMap->getOptions(),
        ];
    }

    private static function convertToConfigMap(Map $configMap, string $namespace, callable $prefixer): KubeConfigMap
    {
        return new KubeConfigMap(
            static::writeSpec($configMap, $namespace, $prefixer)
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachMap(
            static function (Map $configMap, string $namespace, string $prefix) use ($client, $promise): void {
                $prefixer = self::createPrefixer($prefix);
                $kubeConfigMap = self::convertToConfigMap($configMap, $namespace, $prefixer);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $sRepository = $client->configMaps();
                    $result = $sRepository->apply($kubeConfigMap);

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
