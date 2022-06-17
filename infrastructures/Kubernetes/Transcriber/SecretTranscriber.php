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
use Maclof\Kubernetes\Models\Secret as KubeSecret;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
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
 * Deployment Transcriber to translate CompiledDeployment's secrets to Kubernetes Secrets manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SecretTranscriber implements DeploymentInterface
{
    private const BASE64_PREFIX = 'base64:';
    private const NAME_PREFIX = '-secret';

    private static function isValid64(string $value): bool
    {
        return str_starts_with($value, self::BASE64_PREFIX);
    }

    /**
     * @param string|array<string|int, mixed> $value
     * @return string|array<string|int, mixed>
     */
    private static function encode(int | string | array $value): string | array
    {
        if (is_array($value)) {
            foreach ($value as $key => &$subValue) {
                $subValue = self::encode($subValue);
            }

            return $value;
        }

        if (!is_string($value) || !self::isValid64($value)) {
            return base64_encode((string) $value);
        }

        return substr($value, strlen(self::BASE64_PREFIX));
    }

    private static function convertToSecret(Secret $secret, string $namespace): ?KubeSecret
    {
        $provider = $secret->getProvider();
        if ('map' !== $provider) {
            return null;
        }

        return new KubeSecret([
            'metadata' => [
                'name' => $secret->getName() . self::NAME_PREFIX,
                'namespace' => $namespace,
                'labels' => [
                    'name' => $secret->getName(),
                ],
            ],
            'type' => 'Opaque',
            'data' => self::encode($secret->getOptions()),
        ]);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachSecret(
            static function (Secret $secret, string $namespace) use ($client, $promise) {
                $kubeSecret = self::convertToSecret($secret, $namespace);

                if (!$kubeSecret instanceof \Maclof\Kubernetes\Models\Secret) {
                    return;
                }

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $sRepository = $client->secrets();
                    $name = $kubeSecret->getMetadata('name') ?? $secret->getName() . self::NAME_PREFIX;
                    if ($sRepository->exists($name)) {
                        $result = $sRepository->update($kubeSecret);
                    } else {
                        $result = $sRepository->create($kubeSecret);
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
