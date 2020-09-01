<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as VolumeBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

class BuildVolumes
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private SendHistory $sendHistory;

    public function __construct(
        SendHistory $sendHistory,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->sendHistory = $sendHistory;
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        VolumeBuilder $builder,
        CompiledDeployment $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $jobUnit,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        $workspace->runInRoot(
            function (string $root) use ($builder, $compiledDeployment, $jobUnit, $client, $manager) {
                $builder->buildVolumes(
                    $compiledDeployment,
                    $root,
                    new Promise(
                        function (string $buildSuccess) use ($jobUnit) {
                            ($this->sendHistory)(
                                $jobUnit,
                                static::class . ':Result',
                                ['docker_output' => $buildSuccess]
                            );
                        },
                        static::buildFailurePromise(
                            $client,
                            $manager,
                            'teknoo.paas.error.recipe.volumes.building_error',
                            500,
                            $this->responseFactory,
                            $this->streamFactory
                        )
                    )
                );
            }
        );

        return $this;
    }
}
