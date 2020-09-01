<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
                $promise->fail(new \RuntimeException('teknoo.east.paas.error.job.unknown_error', 500));

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
