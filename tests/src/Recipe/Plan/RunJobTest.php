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

namespace Teknoo\Tests\East\Paas\Recipe\Plan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Paas\Recipe\Plan\RunJob;
use Teknoo\East\Paas\Recipe\Step\Job\DeserializeJob;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\Ping;
use Teknoo\East\Paas\Recipe\Step\Misc\SetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Misc\UnsetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildImages;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildVolumes;
use Teknoo\East\Paas\Recipe\Step\Worker\CheckHooksTimeouts;
use Teknoo\East\Paas\Recipe\Step\Worker\CheckTimeouts;
use Teknoo\East\Paas\Recipe\Step\Worker\CloneRepository;
use Teknoo\East\Paas\Recipe\Step\Worker\CompileDeployment;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureClusterClient;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureConductor;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureImagesBuilder;
use Teknoo\East\Paas\Recipe\Step\Worker\Deploying;
use Teknoo\East\Paas\Recipe\Step\Worker\Exposing;
use Teknoo\East\Paas\Recipe\Step\Worker\HookingDeployment;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\PlanInterface;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\BasePlanTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(RunJob::class)]
class RunJobTest extends TestCase
{
    use BasePlanTestTrait;

    public function buildPlan(
        ?RecipeInterface $recipe = null,
        bool $withTimeoutsChecks = true,
    ): PlanInterface {
        return new RunJob(
            $recipe ?? $this->createStub(RecipeInterface::class),
            $this->createStub(DispatchHistoryInterface::class),
            $this->createStub(Ping::class),
            $this->createStub(SetTimeLimit::class),
            $this->createStub(ReceiveJob::class),
            $this->createStub(DeserializeJob::class),
            $this->createStub(PrepareWorkspace::class),
            $this->createStub(ConfigureCloningAgent::class),
            $this->createStub(CloneRepository::class),
            $this->createStub(ConfigureConductor::class),
            $this->createStub(ReadDeploymentConfiguration::class),
            $this->createStub(CompileDeployment::class),
            $this->createStub(HookingDeployment::class),
            $this->createStub(ConfigureImagesBuilder::class),
            $this->createStub(BuildImages::class),
            $this->createStub(BuildVolumes::class),
            $this->createStub(ConfigureClusterClient::class),
            $this->createStub(Deploying::class),
            $this->createStub(Exposing::class),
            $this->createStub(DispatchResultInterface::class),
            $this->createStub(UnsetTimeLimit::class),
            $this->createStub(SendHistoryInterface::class),
            $withTimeoutsChecks ? $this->createStub(CheckTimeouts::class) : null,
            $withTimeoutsChecks ? $this->createStub(CheckHooksTimeouts::class) : null,
        );
    }

    /**
     * @return array<int, string>
     */
    private function listCookedSteps(bool $withTimeoutsChecks): array
    {
        $cooked = [];
        $recipe = $this->createStub(RecipeInterface::class);
        $recipe->method('require')->willReturnSelf();
        $recipe->method('onError')->willReturnSelf();
        $recipe->method('cook')->willReturnCallback(
            function (mixed $action, string $name, array $with = [], ?int $position = null) use (&$cooked, $recipe) {
                $cooked[(int) $position][] = $name;

                return $recipe;
            }
        );

        $this->buildPlan($recipe, $withTimeoutsChecks)->train($this->createStub(ChefInterface::class));

        return $cooked;
    }

    public function testTimeoutsChecksAreCooked(): void
    {
        $cooked = $this->listCookedSteps(true);

        $this->assertSame([CheckTimeouts::class], $cooked[RunJob::STEP_CHECK_TIMEOUTS] ?? []);
        $this->assertSame([CheckHooksTimeouts::class], $cooked[RunJob::STEP_CHECK_HOOKS_TIMEOUTS] ?? []);
    }

    public function testTimeoutsChecksAreOptionals(): void
    {
        $cooked = $this->listCookedSteps(false);

        $this->assertNotEmpty($cooked[RunJob::STEP_DESERIALIZE_JOB] ?? []);
        $this->assertArrayNotHasKey(RunJob::STEP_CHECK_TIMEOUTS, $cooked);
        $this->assertArrayNotHasKey(RunJob::STEP_CHECK_HOOKS_TIMEOUTS, $cooked);
    }
}
