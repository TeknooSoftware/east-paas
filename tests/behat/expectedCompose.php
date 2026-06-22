<?php

declare(strict_types=1);

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

namespace Teknoo\Tests\East\Paas\Behat;

use RuntimeException;

use function file_get_contents;
use function is_dir;
use function ksort;
use function scandir;

return static function (
    string $prefix,
    string $withQuota,
    string $withDefaults,
    string $projectName,
    bool $withJob,
    bool $withCondition,
    string $provider,
): array {
    if ('' !== $withQuota) {
        //Quota variants only ever run without prefix/job in the suite; a prefixed/jobbed quota scenario would
        //load this (wrong) golden and fail loudly, flagging that a new fixture must be generated.
        $variant = 'quota-' . $withQuota;
    } elseif ('' !== $prefix && $withJob) {
        $variant = 'with-prefix-job';
    } elseif ('' !== $prefix) {
        $variant = 'with-prefix';
    } elseif ($withJob) {
        $variant = 'with-job';
    } else {
        $variant = 'base';
    }

    $dir = __DIR__ . '/expected/compose/' . $variant;
    if (!is_dir($dir)) {
        throw new RuntimeException(
            "Missing Docker Compose golden fixture for variant \"$variant\" (dir: $dir). "
            . 'Regenerate it with DUMP_COMPOSE=1.'
        );
    }

    $read = static fn (string $name): string => (string) file_get_contents($dir . '/' . $name);

    $referencedFiles = [];
    foreach (['configs', 'secrets'] as $subDir) {
        $absSubDir = $dir . '/refs/' . $subDir;
        if (!is_dir($absSubDir)) {
            continue;
        }

        foreach ((array) scandir($absSubDir) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $referencedFiles[$subDir . '/' . $entry] = (string) file_get_contents($absSubDir . '/' . $entry);
        }
    }
    ksort($referencedFiles);

    return [
        'compose.yaml' => $read('compose.yaml'),
        'deploy.yml' => $read('deploy.yml'),
        'expose.yml' => $read('expose.yml'),
        'traefik' => $read('traefik.yml'),
        'referencedFiles' => $referencedFiles,
    ];
};
