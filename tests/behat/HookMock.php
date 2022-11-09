<?php

declare(strict_types=1);

namespace Teknoo\Tests\East\Paas\Behat;

use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

class HookMock implements HookInterface {
    public function setPath(string $path): HookInterface
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options, PromiseInterface $promise): HookInterface
    {
        $promise->success();
        return $this;
    }

    public function run(PromiseInterface $promise): HookInterface
    {
        $promise->success('foo');
        return $this;
    }
}
