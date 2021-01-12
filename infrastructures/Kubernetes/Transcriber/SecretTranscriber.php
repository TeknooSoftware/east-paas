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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Maclof\Kubernetes\Client as KubernetesClient;
use Maclof\Kubernetes\Models\Secret as KubeSecret;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Container\Secret;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SecretTranscriber implements DeploymentInterface
{
    private const BASE64_PREFIX = 'base64:';

    private static function isValid64(string $value): bool
    {
        return 0 === \strpos($value, static::BASE64_PREFIX);
    }

    /**
     * @param string|array<string|int, mixed> $value
     * @return string|array<string|int, mixed>
     */
    private static function encode($value)
    {
        if (\is_array($value)) {
            foreach ($value as $key => &$subValue) {
                $subValue = static::encode($subValue);
            }

            return $value;
        }

        if (!\is_string($value) || !static::isValid64($value)) {
            return \base64_encode((string) $value);
        }

        return \substr($value, \strlen(static::BASE64_PREFIX));
    }

    private static function convertToSecret(Secret $secret, string $namespace): ?KubeSecret
    {
        $provider = $secret->getProvider();
        if ('map' !== $provider) {
            return null;
        }

        return new KubeSecret([
            'metadata' => [
                'name' => $secret->getName() . '-secret',
                'namespace' => $namespace,
                'labels' => [
                    'name' => $secret->getName(),
                ],
            ],
            'type' => 'Opaque',
            'data' => static::encode($secret->getOptions()),
        ]);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachSecret(
            static function (Secret $secret, string $namespace) use ($client, $promise) {
                $kubeSecret = static::convertToSecret($secret, $namespace);

                if (null === $kubeSecret) {
                    return;
                }

                try {
                    $sRepository = $client->secrets();
                    if ($sRepository->exists($kubeSecret->getMetadata('name'))) {
                        $result = $sRepository->update($kubeSecret);
                    } else {
                        $result = $sRepository->create($kubeSecret);
                    }

                    $promise->success($result);
                } catch (\Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
