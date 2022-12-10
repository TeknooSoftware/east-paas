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
 * "Deployment transcriber" to translate CompiledDeployment's secrets to Kubernetes Secrets manifest.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SecretTranscriber implements DeploymentInterface
{
    use CommonTrait;

    private const BASE64_PREFIX = 'base64:';
    private const NAME_SUFFIX = '-secret';

    protected static function isValid64(string $value): bool
    {
        return str_starts_with($value, self::BASE64_PREFIX);
    }

    /**
     * @param string|array<string|int, mixed> $value
     * @return string|array<string|int, mixed>
     */
    protected static function encode(int | string | array $value): string | array
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

    /**
     * @return array<string, mixed>
     */
    protected static function writeSpec(Secret $secret, string $namespace, callable $prefixer): array
    {
        return [
            'metadata' => [
                'name' => $prefixer($secret->getName() . self::NAME_SUFFIX),
                'namespace' => $namespace,
                'labels' => [
                    'name' => $prefixer($secret->getName()),
                ],
            ],
            'type' => match ($secret->getType()) {
                'tls' => 'kubernetes.io/tls',
                'default' => 'Opaque',
                default => $secret->getType(),
            },
            'data' => self::encode($secret->getOptions()),
        ];
    }

    private static function convertToSecret(Secret $secret, string $namespace, callable $prefixer): ?KubeSecret
    {
        $provider = $secret->getProvider();
        if ('map' !== $provider) {
            return null;
        }

        return new KubeSecret(
            static::writeSpec($secret, $namespace, $prefixer)
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $compiledDeployment->foreachSecret(
            static function (Secret $secret, string $namespace, string $prefix,) use ($client, $promise) {
                $prefixer = self::createPrefixer($prefix);
                $kubeSecret = self::convertToSecret($secret, $namespace, $prefixer);

                if (!$kubeSecret instanceof KubeSecret) {
                    return;
                }

                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $sRepository = $client->secrets();
                    $result = $sRepository->apply($kubeSecret);

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
