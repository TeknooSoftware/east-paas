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

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use SensitiveParameter;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;

/**
 * Step, run at the beginning of a job, to check timeouts of external operations (Symfony Process to clone
 * repositories, to build images or to run Ansible, Kubernetes client, etc.) against the worker's time limit, and add
 * a warning in the job's history for each timeout bigger than this time limit (the worker will be stopped before
 * reaching them). Unlimited timeouts (null or lower or equal to zero) are ignored.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CheckTimeouts
{
    use TimeoutsCheckerTrait;

    /**
     * @param array<string, int|float|null> $timeouts timeouts in seconds, indexed by their parameter's name
     */
    public function __construct(
        private readonly DispatchHistoryInterface $dispatchHistory,
        private readonly int $timeLimit,
        private readonly array $timeouts,
    ) {
    }

    public function __invoke(
        string $projectId,
        string $envName,
        #[SensitiveParameter] JobUnitInterface $jobUnit,
    ): self {
        $warnings = [];
        foreach ($this->timeouts as $name => $timeout) {
            if (null !== $timeout && $timeout > 0 && $this->timeLimit > 0 && $timeout > $this->timeLimit) {
                $warnings[] = "The timeout `{$name}` ({$timeout}s) is bigger than the worker time limit "
                    . "`teknoo.east.paas.worker.time_limit` ({$this->timeLimit}s), the worker will be stopped "
                    . 'before reaching this timeout';
            }
        }

        $this->dispatchWarnings($this->dispatchHistory, $warnings, $projectId, $envName, $jobUnit);

        return $this;
    }
}
