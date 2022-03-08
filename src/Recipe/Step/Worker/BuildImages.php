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
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Throwable;

/**
 * Step, run in the deployment workspace filesystem, to build all OCI images, defined in the compiled deployment
 * instance and push to the image repository.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class BuildImages
{
    public function __construct(
        private DispatchHistoryInterface $dispatchHistory,
    ) {
    }

    public function __invoke(
        ImageBuilder $builder,
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
            ) {
                $builder->buildImages(
                    $compiledDeployment,
                    $root,
                    new Promise(
                        function (string $buildSuccess) use ($projectId, $envName, $jobUnit) {
                            ($this->dispatchHistory)(
                                $projectId,
                                $envName,
                                $jobUnit->getId(),
                                self::class . ':Result',
                                ['build_output' => $buildSuccess]
                            );
                        },
                        fn (Throwable $error) => throw new RuntimeException(
                            'teknoo.east.paas.error.recipe.images.building_error',
                            500,
                            $error
                        )
                    )
                );
            }
        );

        return $this;
    }
}
