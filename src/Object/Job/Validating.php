<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object\Job;

use Teknoo\East\Paas\Object\Job;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Job
 */
class Validating implements StateInterface
{
    use StateTrait;

    public function validate(): \Closure
    {
        return function (\DateTimeInterface $date): Job {
            $this->addToHistory('teknoo.paas.jobs.configured', $date, false);

            return $this;
        };
    }
}
