<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object\Job;

use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Job
 */
class Terminated implements StateInterface
{
    use StateTrait;

    public function isRunnable(): \Closure
    {
        return function (PromiseInterface $promise): Job {
            $code = 501;
            $history = $this->getHistory();
            if (!$history instanceof History) {
                $promise->fail(new \RuntimeException('teknoo.paas.error.job.unknown_error', 500));

                return $this;
            }

            $extra = $history->getExtra();
            if (isset($extra['code'])) {
                $code = $extra['code'];
            }

            $promise->fail(new \RuntimeException($history->getMessage(), $code));

            return $this;
        };
    }
}
