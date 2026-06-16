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

use function preg_replace;
use function strtolower;
use function trim;

/**
 * Trait factorising common helpers shared by the Docker Compose transcribers: a name prefixer, a DNS-safe
 * name sanitiser (Compose project / service / network names) and a result cleaner removing sensitive
 * information to prevent secret leaks or massive data output in the job History.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait CommonTrait
{
    /**
     * @return callable(string): string
     */
    private static function createPrefixer(?string $prefix): callable
    {
        return static function (string $value) use ($prefix): string {
            if (empty($prefix)) {
                return $value;
            }

            return $prefix . '-' . $value;
        };
    }

    /**
     * Lowercases the value and replaces every character outside `[a-z0-9-]` by a dash, collapsing repeated
     * dashes and trimming leading/trailing dashes, to produce a DNS-safe Compose project/service/network
     * name.
     */
    private static function sanitizeDns(string $value): string
    {
        $value = strtolower($value);
        $value = (string) preg_replace('#[^a-z0-9-]+#', '-', $value);
        $value = (string) preg_replace('#-+#', '-', $value);

        return trim($value, '-');
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private static function cleanResult(?array $result): array
    {
        if (null !== $result && isset($result['secrets'])) {
            $result['secrets'] = '#removed#';
        }

        if (null !== $result && isset($result['configs'])) {
            $result['configs'] = '#removed#';
        }

        if (null !== $result && isset($result['files'])) {
            $result['files'] = '#removed#';
        }

        return $result ?? [];
    }
}
