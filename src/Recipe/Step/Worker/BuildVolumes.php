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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as VolumeBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * Step, run in the deployment workspace filesystem, to build all persisted volumes, defined in the compiled deployment
 * instance and push to the image repository.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class BuildVolumes
{
    public function __construct(
        private DispatchHistoryInterface $dispatchHistory,
        private ErrorFactoryInterface $errorFactory,
    ) {
    }

    public function __invoke(
        VolumeBuilder $builder,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        string $projectId,
        string $envName,
        JobUnitInterface $jobUnit,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        $workspace->runInRoot(
            function (
                string $root
            ) use (
                $builder,
                $compiledDeployment,
                $projectId,
                $envName,
                $jobUnit,
                $client,
                $manager
            ) {
                $builder->buildVolumes(
                    $compiledDeployment,
                    $root,
                    new Promise(
                        function (string $buildSuccess) use ($projectId, $envName, $jobUnit) {
                            ($this->dispatchHistory)(
                                $projectId,
                                $envName,
                                $jobUnit->getId(),
                                static::class . ':Result',
                                ['build_output' => $buildSuccess]
                            );
                        },
                        $this->errorFactory->buildFailurePromise(
                            $client,
                            $manager,
                            500,
                            'teknoo.east.paas.error.recipe.volumes.building_error',
                        )
                    )
                );
            }
        );

        return $this;
    }
}
