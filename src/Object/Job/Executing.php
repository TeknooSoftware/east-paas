<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object\Job;

use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Job
 */
class Executing implements StateInterface
{
    use StateTrait;

    public function isRunnable(): \Closure
    {
        return function (PromiseInterface $promise): Job {
            $promise->success();

            return $this;
        };
    }
}
