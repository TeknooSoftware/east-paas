<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe\Step\History;

use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job;

/**
 * Step to add a new event in a job. The new event will be updated in the workplan with previous
 * event.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class AddHistory
{
    public function __invoke(Job $job, History $history, ManagerInterface $manager): self
    {
        $job->addFromHistory($history, static function (History $history) use ($manager): void {
            $manager->updateWorkPlan([History::class => $history]);
        });

        return $this;
    }
}
