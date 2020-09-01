<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\History;

use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job;

class AddHistory
{
    public function __invoke(Job $job, History $history, ManagerInterface $manager): self
    {
        $job->addFromHistory($history, static function (History $history) use ($manager) {
            $manager->updateWorkPlan([History::class => $history]);
        });

        return $this;
    }
}
