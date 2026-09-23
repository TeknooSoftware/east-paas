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

use SensitiveParameter;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Hook\TimeoutAwareHookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Step, run after the compilation of the deployment and before running hooks, to ask to each hook used by the job
 * (and implementing `TimeoutAwareHookInterface`) to check its timeout against the worker's time limit. A warning is
 * added in the job's history for each hook failing the promise (the worker will be stopped before reaching its
 * timeout).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CheckHooksTimeouts
{
    use TimeoutsCheckerTrait;

    public function __construct(
        private readonly DispatchHistoryInterface $dispatchHistory,
        private readonly int $timeLimit,
    ) {
    }

    public function __invoke(
        CompiledDeploymentInterface $compiledDeployment,
        string $projectId,
        string $envName,
        #[SensitiveParameter] JobUnitInterface $jobUnit,
    ): self {
        $warnings = [];
        $hookName = '';

        /** @var Promise<mixed, mixed, mixed> $promise */
        $promise = new Promise(
            onFail: static function (Throwable $error) use (&$warnings, &$hookName): void {
                $warnings[] = "Hook `{$hookName}`: {$error->getMessage()}";
            },
        );

        $promise->allowReuse();

        $compiledDeployment->foreachHook(
            function (HookInterface $hook, string $name = '') use ($promise, &$hookName): void {
                if (!$hook instanceof TimeoutAwareHookInterface) {
                    return;
                }

                $hookName = $name;
                $hook->checkTimeLimit($this->timeLimit, $promise);
            }
        );

        $this->dispatchWarnings($this->dispatchHistory, $warnings, $projectId, $envName, $jobUnit);

        return $this;
    }
}
