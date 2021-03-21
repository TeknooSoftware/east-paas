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

use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as VolumeBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

/**
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
    use ErrorTrait;
    use PsrFactoryTrait;

    private DispatchHistoryInterface $dispatchHistory;

    public function __construct(
        DispatchHistoryInterface $dispatchHistory,
        MessageFactoryInterface $messageFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->dispatchHistory = $dispatchHistory;
        $this->setMessageFactory($messageFactory);
        $this->setStreamFactory($streamFactory);
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
                        static::buildFailurePromise(
                            $client,
                            $manager,
                            'teknoo.east.paas.error.recipe.volumes.building_error',
                            500,
                            $this->messageFactory,
                            $this->streamFactory
                        )
                    )
                );
            }
        );

        return $this;
    }
}
