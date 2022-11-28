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
use Maclof\Kubernetes\Models\ConfigMap as KubeConfigMap;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

use function base64_encode;
use function is_array;
use function is_string;
use function strlen;
use function str_starts_with;
use function substr;

/**
 * "Deployment transcriber" to translate CompiledDeployment's map to Kubernetes ConfigMaps manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ConfigMapTranscriber implements DeploymentInterface
{
    private const BASE64_PREFIX = 'base64:';
    private const NAME_SUFFIX = '-map';

    public static function isValid64(string $value): bool
    {
        return str_starts_with($value, self::BASE64_PREFIX);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function writeSpec(Map $configMap, string $namespace): array
    {
        return [
            'metadata' => [
                'name' => $configMap->getName() . self::NAME_SUFFIX,
                'namespace' => $namespace,
                'labels' => [
                    'name' => $configMap->getName(),
                ],
            ],
            'data' => $configMap->getOptions(),
        ];
    }

    private static function convertToConfigMap(Map $configMap, string $namespace): KubeConfigMap
    {
        return new KubeConfigMap(
            static::writeSpec($configMap, $namespace)
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachMap(
            static function (Map $configMap, string $namespace) use ($client, $promise) {
                $kubeConfigMap = self::convertToConfigMap($configMap, $namespace);

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $sRepository = $client->configMaps();
                    $name = $kubeConfigMap->getMetadata('name') ?? $configMap->getName() . self::NAME_SUFFIX;
                    if ($sRepository->exists($name)) {
                        $result = $sRepository->update($kubeConfigMap);
                    } else {
                        $result = $sRepository->create($kubeConfigMap);
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
