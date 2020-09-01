<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Composer;

use Symfony\Component\Process\Process;

return [
    ComposerHook::class => static function (): ComposerHook {
        return new ComposerHook(
            __DIR__ . '/../../bin/composer.phar',
            static function (array $command, string $cwd): Process {
                return new Process($command, $cwd);
            }
        );
    },
];
