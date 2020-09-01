<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper;

use Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin BuilderWrapper
 */
class Generator implements StateInterface
{
    use StateTrait;

    private function getUrl(): \Closure
    {
        return function (): string {
            throw new \RuntimeException('Builder is in generator state');
        };
    }

    private function getAuth(): \Closure
    {
        return function (): ?XRegistryAuth {
            throw new \RuntimeException('Builder is in generator state');
        };
    }
}
