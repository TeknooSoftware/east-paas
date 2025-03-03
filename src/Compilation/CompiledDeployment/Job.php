<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\CompletionMode;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\SuccessCondition;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized job, scheduled like a cron or to execute directly during the
 * deployment. A Job can have several pods, pods can be executed sequentially or in parallel.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Job implements ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @param array<string, Pod> $pods
     */
    public function __construct(
        private readonly string $name,
        private readonly array $pods,
        private readonly int $completionsCount = 1,
        private readonly bool $isParallel = false,
        private readonly CompletionMode $completion = CompletionMode::Common,
        private readonly ?SuccessCondition $successCondition = null,
        private readonly ?int $timeLimit = null,
        private readonly Planning $planning = Planning::DuringDeployment,
        private readonly ?string $planningSchedule = null,
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getCompletion(): CompletionMode
    {
        return $this->completion;
    }

    public function getCompletionsCount(): int
    {
        return $this->completionsCount;
    }

    public function isParallel(): bool
    {
        return $this->isParallel;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    public function getPlanningSchedule(): ?string
    {
        return $this->planningSchedule;
    }

    /**
     * @return array<string, Pod>
     */
    public function getPods(): array
    {
        return $this->pods;
    }

    public function getSuccessCondition(): ?SuccessCondition
    {
        return $this->successCondition;
    }

    public function getTimeLimit(): ?int
    {
        return $this->timeLimit;
    }
}
