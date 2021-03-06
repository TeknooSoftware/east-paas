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
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * Step to read the paas file yaml from the cloned source repository and load it into the conductor
 * to compile it.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ReadDeploymentConfiguration
{
    public function __construct(
        private ErrorFactoryInterface $errorFactory,
    ) {
    }

    public function __invoke(
        JobWorkspaceInterface $workspace,
        ConductorInterface $conductor,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        $workspace->loadDeploymentIntoConductor(
            $conductor,
            new Promise(
                null,
                $this->errorFactory->buildFailurePromise(
                    $client,
                    $manager,
                    500,
                    'teknoo.east.paas.error.recipe.configuration.read_error',
                )
            )
        );

        return $this;
    }
}
