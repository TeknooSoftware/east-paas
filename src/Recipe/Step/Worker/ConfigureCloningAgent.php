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

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use RuntimeException;
use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\ChefInterface;
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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConfigureCloningAgent
{
    public function __construct(
        private readonly CloningAgentInterface $agent,
    ) {
    }

    public function __invoke(
        #[SensitiveParameter] JobUnitInterface $job,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        /** @var Promise<CloningAgentInterface, mixed, mixed> $promise */
        $promise = new Promise(
            onSuccess: static function (CloningAgentInterface $agent) use ($manager): void {
                $manager->updateWorkPlan([CloningAgentInterface::class => $agent]);
            },
            onFail: static fn (#[SensitiveParameter] Throwable $error): ChefInterface => $manager->error(
                new RuntimeException(
                    'teknoo.east.paas.error.recipe.agent.configuration_error',
                    500,
                    $error,
                ),
            ),
        );

        $job->configureCloningAgent(
            $this->agent,
            $workspace,
            $promise,
        );

        return $this;
    }
}
