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

namespace Teknoo\Tests\East\Paas\Recipe\Plan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Recipe\Plan\NewJob;
use Teknoo\East\Paas\Recipe\Step\Job\CreateNewJob;
use Teknoo\East\Paas\Recipe\Step\Job\PrepareJob;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DispatchError;
use Teknoo\East\Paas\Recipe\Step\Misc\GetVariables;
use Teknoo\East\Paas\Recipe\Step\Misc\Ping;
use Teknoo\East\Paas\Recipe\Step\Misc\SetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Misc\UnsetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\Recipe\PlanInterface;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\BasePlanTestTrait;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(NewJob::class)]
class NewJobTest extends TestCase
{
    use BasePlanTestTrait;

    public function buildPlan(): PlanInterface
    {
        return new NewJob(
            $this->createMock(RecipeInterface::class),
            $this->createMock(Ping::class),
            $this->createMock(SetTimeLimit::class),
            $this->createMock(GetProject::class),
            $this->createMock(GetEnvironment::class),
            $this->createMock(GetVariables::class),
            $this->createMock(CreateNewJob::class),
            $this->createMock(PrepareJob::class),
            $this->createMock(SaveJob::class),
            $this->createMock(SerializeJob::class),
            $this->createMock(DispatchJobInterface::class),
            $this->createMock(SendJobInterface::class),
            $this->createMock(UnsetTimeLimit::class),
            $this->createMock(DispatchError::class),
        );
    }
}
