<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use RuntimeException;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Hook\HookAwareInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Throwable;

/**
 * Step to run all configured hooks for this project in the paas yaml file before run the deployment.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HookingDeployment
{
    public function __construct(
        private readonly DispatchHistoryInterface $dispatchHistory,
    ) {
    }

    public function __invoke(
        JobWorkspaceInterface $workspace,
        CompiledDeploymentInterface $compiledDeployment,
        string $projectId,
        string $envName,
        JobUnitInterface $jobUnit,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        /** @var Promise<string, mixed, mixed> $promise */
        $promise = new Promise(
            function (string $buildSuccess) use ($projectId, $envName, $jobUnit) {
                ($this->dispatchHistory)(
                    $projectId,
                    $envName,
                    $jobUnit->getId(),
                    self::class . ':Result',
                    ['hook_output' => $buildSuccess]
                );
            },
            fn (Throwable $error) => throw $error,
        );

        $workspace->runInRepositoryPath(
            function (
                $path
            ) use (
                $compiledDeployment,
                $promise,
                $jobUnit,
                $workspace,
                $manager,
            ) {
                try {
                    $compiledDeployment->foreachHook(
                        static function (HookInterface $hook) use ($path, $promise, $jobUnit, $workspace) {
                            if ($hook instanceof HookAwareInterface) {
                                $hook->setContext($jobUnit, $workspace);
                            }

                            $hook->setPath($path);
                            $hook->run($promise);
                        }
                    );
                } catch (Throwable $error) {
                    $manager->error(
                        new RuntimeException(
                            'teknoo.east.paas.error.recipe.hook.building_error',
                            500,
                            $error
                        )
                    );
                }
            }
        );

        return $this;
    }
}
