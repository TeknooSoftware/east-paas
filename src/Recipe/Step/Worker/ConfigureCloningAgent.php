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
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Throwable;

/**
 * Step to configure the cloning agent with project's configuration to fetch source from the repository
 * and push it into the workplan.
 * (The agent injected is a clone of the original agent, the original is "immutable").
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ConfigureCloningAgent
{
    public function __construct(
        private CloningAgentInterface $agent,
    ) {
    }

    public function __invoke(
        JobUnitInterface $job,
        JobWorkspaceInterface $workspace,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        /** @var Promise<CloningAgentInterface, mixed, mixed> $promise */
        $promise = new Promise(
            static function (CloningAgentInterface $agent) use ($manager) {
                $manager->updateWorkPlan([CloningAgentInterface::class => $agent]);
            },
            fn (Throwable $error) => $manager->error(
                new RuntimeException(
                    'teknoo.east.paas.error.recipe.agent.configuration_error',
                    500,
                    $error
                )
            )
        );

        $job->configureCloningAgent(
            $this->agent,
            $workspace,
            $promise
        );

        return $this;
    }
}
