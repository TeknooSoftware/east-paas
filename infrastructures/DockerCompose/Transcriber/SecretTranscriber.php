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

use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_map;
use function base64_decode;
use function count;
use function implode;
use function is_array;
use function is_scalar;
use function is_string;
use function reset;
use function str_starts_with;
use function strlen;
use function substr;
use const PHP_EOL;

/**
 * "Deployment transcriber" translating CompiledDeployment's secrets (provider `map`, carrying inline values
 * in their options) to Compose `secrets` entries backed by files pushed to the host.
 *
 * Each secret becomes a single Compose secret `{ <prefixed>-secret: { file: ./secrets/<prefixed>-secret } }`
 * (the name consumed by the deployment transcribers) plus, when its options carry several keys, one
 * Compose secret per key (`<prefixed>-secret__<key>`) so individual keys can be mounted or injected as env
 * vars. A `base64:` prefixed value is decoded before being written, mirroring the Kubernetes
 * SecretTranscriber convention. The per-key entries also cover the `tls.crt`/`tls.key` pair consumed by the
 * IngressTranscriber for per-ingress TLS.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SecretTranscriber implements DeploymentInterface
{
    use CommonTrait;

    private const string BASE64_PREFIX = 'base64:';

    private const string NAME_SUFFIX = '-secret';

    private static function decode(mixed $value): string
    {
        if (is_array($value)) {
            return implode(PHP_EOL, array_map(self::decode(...), $value));
        }

        if (is_string($value) && str_starts_with($value, self::BASE64_PREFIX)) {
            return (string) base64_decode(substr($value, strlen(self::BASE64_PREFIX)), true);
        }

        return self::scalarToString($value);
    }

    private static function scalarToString(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Build the content of the aggregated secret file: the bare value when a single key is present,
     * otherwise a newline-joined `key=value` env-file representation of every option.
     *
     * @param array<string|int, mixed> $options
     */
    private static function aggregate(array $options): string
    {
        if (1 === count($options)) {
            return self::decode(reset($options));
        }

        $lines = [];
        foreach ($options as $key => $value) {
            $lines[] = (string) $key . '=' . self::decode($value);
        }

        return implode(PHP_EOL, $lines);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        GenerationInterface $generation,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $compiledDeployment->foreachSecret(
            static function (Secret $secret, string $prefix) use ($generation, $promise): void {
                if ('map' !== $secret->getProvider()) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);

                try {
                    $baseName = (string) $prefixer($secret->getName() . self::NAME_SUFFIX);
                    $options = $secret->getOptions();
                    $entries = [];

                    //One Compose secret per key, so a single key can be mounted/injected individually
                    //(this also exposes the tls.crt/tls.key files used by the ingress transcriber).
                    foreach ($options as $key => $value) {
                        $secretName = $baseName . '__' . (string) $key;
                        $filePath = 'secrets/' . $secretName;

                        $generation
                            ->addSecret($secretName, ['file' => './' . $filePath])
                            ->addFile($filePath, self::decode($value));

                        $entries[$secretName] = ['file' => './' . $filePath];
                    }

                    //A single aggregated Compose secret matching the name consumed by the deployment
                    //transcribers (`<prefixed>-secret`); its file holds the first option's value when only
                    //one key is present, otherwise the serialized set of keys.
                    $aggregatePath = 'secrets/' . $baseName;
                    $generation
                        ->addSecret($baseName, ['file' => './' . $aggregatePath])
                        ->addFile($aggregatePath, self::aggregate($options));

                    $entries[$baseName] = ['file' => './' . $aggregatePath];

                    $promise->success(['secrets' => $entries]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
