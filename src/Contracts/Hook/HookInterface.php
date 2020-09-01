<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Hook;

interface HookInterface
{
    public function setPath(string $path): HookInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): HookInterface;

    public function run(): HookInterface;
}
