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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

/**
 * Trait to remove some information in kubernetes's result to prevent secret's leaks or massive data output
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait CommonTrait
{
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
