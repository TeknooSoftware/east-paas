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
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

class CompileDeployment
{
    use ErrorTrait;
    use PsrFactoryTrait;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        ManagerInterface $manager,
        ClientInterface $client,
        ConductorInterface $conductor
    ): self {
        $conductor->compileDeployment(
            new Promise(
                static function (CompiledDeployment $deployment) use ($manager) {
                    $manager->updateWorkPlan([
                        CompiledDeployment::class => $deployment,
                    ]);
                },
                static::buildFailurePromise(
                    $client,
                    $manager,
                    'teknoo.paas.error.recipe.configuration.compilation_error',
                    500,
                    $this->responseFactory,
                    $this->streamFactory
                )
            )
        );

        return $this;
    }
}
