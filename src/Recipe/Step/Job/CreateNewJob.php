<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Job;

use Teknoo\East\Paas\Object\Job;
use Teknoo\Recipe\ChefInterface;

class CreateNewJob
{
    public function __invoke(ChefInterface $chef): self
    {
        $chef->updateWorkPlan(['job' => new Job()]);

        return $this;
    }
}
