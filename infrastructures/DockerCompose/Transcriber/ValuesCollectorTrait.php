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

use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;

use function array_map;
use function base64_decode;
use function implode;
use function is_array;
use function is_scalar;
use function is_string;
use function preg_replace;
use function str_starts_with;
use function strlen;
use function substr;

use const PHP_EOL;

/**
 * Trait factorising the pre-scan of the CompiledDeployment's `map`-provider secrets and maps, keyed by their
 * raw (unprefixed) name, so the Docker Compose transcribers can materialise their values: per-key Compose
 * secrets/configs files, per-container env files and per-ingress TLS certificates.
 *
 * A `base64:` prefixed secret value is decoded, an array value is joined with a newline, mirroring the
 * Kubernetes SecretTranscriber convention. Secrets from any other provider (vault, external...) are not
 * available on a plain Docker host and are ignored by the scan (a reference to one of them fails loudly in
 * the pods transcription).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait ValuesCollectorTrait
{
    private const string BASE64_PREFIX = 'base64:';

    private const string MAP_PROVIDER = 'map';

    private static function decodeSecretValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(PHP_EOL, array_map(self::decodeSecretValue(...), $value));
        }

        if (is_string($value) && str_starts_with($value, self::BASE64_PREFIX)) {
            return (string) base64_decode(substr($value, strlen(self::BASE64_PREFIX)), true);
        }

        return self::valueToString($value);
    }

    private static function valueToString(mixed $value): string
    {
        if (is_array($value)) {
            return implode(PHP_EOL, array_map(self::valueToString(...), $value));
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Produce a file/Compose-resource safe key name (Compose names accept only `[A-Za-z0-9._-]`).
     */
    private static function sanitizeKey(string $key): string
    {
        return (string) preg_replace('#[^A-Za-z0-9._-]+#', '_', $key);
    }

    /**
     * @return array<string, array<string, string>> raw secret name => option key => decoded value
     */
    private static function collectSecrets(CompiledDeploymentInterface $compiledDeployment): array
    {
        $secrets = [];

        $compiledDeployment->foreachSecret(
            static function (Secret $secret, string $prefix) use (&$secrets): void {
                if (self::MAP_PROVIDER !== $secret->getProvider()) {
                    return;
                }

                $options = [];
                foreach ($secret->getOptions() as $key => $value) {
                    $options[(string) $key] = self::decodeSecretValue($value);
                }

                $secrets[$secret->getName()] = $options;
            }
        );

        return $secrets;
    }

    /**
     * @return array<string, array<string, string>> raw map name => option key => value
     */
    private static function collectMaps(CompiledDeploymentInterface $compiledDeployment): array
    {
        $maps = [];

        $compiledDeployment->foreachMap(
            static function (Map $map, string $prefix) use (&$maps): void {
                $options = [];
                foreach ($map->getOptions() as $key => $value) {
                    $options[(string) $key] = self::valueToString($value);
                }

                $maps[$map->getName()] = $options;
            }
        );

        return $maps;
    }
}
