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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use function hash;
use function rtrim;
use function strlen;
use function substr;

/**
 * Trait to remove some information in kubernetes's result to prevent secret's leaks or massive data output
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait CommonTrait
{
    /**
     * Kubernetes limits the value of a label, and of a selector on a label, to 63 characters.
     */
    private const int MAX_LABEL_LENGTH = 63;

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
     * Bounds the name of a Kubernetes object, with its suffix, to the length accepted by Kubernetes. A too long name
     * is truncated and suffixed by a short hash of the full name, to stay unique and stable between deployments.
     * A name which fits is unchanged.
     */
    private static function boundName(string $name, string $suffix, int $maxLength): string
    {
        if (strlen($name . $suffix) <= $maxLength) {
            return $name;
        }

        $hash = substr(hash('sha256', $name), 0, 5);

        return rtrim(substr($name, 0, $maxLength - strlen($suffix) - strlen($hash) - 1), '-') . '-' . $hash;
    }

    /**
     * Bounds the value of a label, or of a selector on this label, to the length accepted by Kubernetes, like
     * `boundName()`: a label and the selectors on it, bounded from the same full value, are always equal.
     * A value which fits is unchanged.
     */
    private static function boundLabel(string $value): string
    {
        return self::boundName($value, '', self::MAX_LABEL_LENGTH);
    }


    /**
     * @param array<string, string|array<string, mixed>> $result
     * @return array<string, string|array<string, mixed>>
     */
    private static function cleanResult(?array $result): array
    {
        if (null !== $result && isset($result['data'])) {
            $result['data'] = '#removed#';
        }

        if (null !== $result && isset($result['metadata']['managedFields'])) {
            $result['metadata']['managedFields'] = '#removed#';
        }

        return $result ?? [];
    }
}
