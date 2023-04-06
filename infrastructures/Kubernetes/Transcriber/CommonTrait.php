<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

/**
 * Trait to remove some information in kubernetes's result to prevent secret's leaks or massive data output
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
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
     * @param array<string, string|array<string, mixed>>|null $result
     * @return array<string, string|array<string, mixed>>|null
     */
    private static function cleanResult(?array $result): ?array
    {
        if (null !== $result && isset($result['data'])) {
            $result['data'] = '#removed#';
        }

        if (null !== $result && isset($result['metadata']['managedFields'])) {
            $result['metadata']['managedFields'] = '#removed#';
        }

        return $result;
    }
}
