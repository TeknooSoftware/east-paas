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

use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;

/**
 * Shared logic for steps checking timeouts of external operations (processes, clients, hooks) against the worker's
 * time limit, to add warnings in the job's history. A timeout bigger than the time limit is useless: the worker will be
 * stopped before.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait TimeoutsCheckerTrait
{
    /**
     * @param string[] $warnings
     */
    private function dispatchWarnings(
        DispatchHistoryInterface $dispatchHistory,
        array $warnings,
        string $projectId,
        string $envName,
        JobUnitInterface $jobUnit,
    ): void {
        if (empty($warnings)) {
            return;
        }

        $dispatchHistory(
            $projectId,
            $envName,
            $jobUnit->getId(),
            self::class . ':Warning',
            ['warnings' => $warnings],
        );
    }
}
