<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HookBuildContainer
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
        JobWorkspaceInterface $workspace,
        CompiledDeployment $compiledDeployment,
        JobUnitInterface $jobUnit,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        $workspace->runInRoot(
            function ($path) use ($compiledDeployment, $jobUnit, $client, $manager) {
                $inError = false;
                $promise = new Promise(
                    function (string $buildSuccess) use ($jobUnit) {
                        ($this->sendHistory)(
                            $jobUnit,
                            static::class . ':Result',
                            ['hook_output' => $buildSuccess]
                        );
                    },
                    function (\Throwable $error) use ($client, $manager, &$inError) {
                        $inError = true;

                        static::buildFailurePromise(
                            $client,
                            $manager,
                            'teknoo.east.paas.error.recipe.hook.building_error',
                            500,
                            $this->responseFactory,
                            $this->streamFactory
                        )($error);
                    }
                );

                $compiledDeployment->foreachHook(
                    static function (HookInterface $hook) use (&$inError, $path, $promise) {
                        if ($inError) {
                            return;
                        }

                        $hook->setPath($path);
                        $hook->run($promise);
                    }
                );
            }
        );

        return $this;
    }
}
