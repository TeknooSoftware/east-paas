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

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Job;

/**
 * Options of a scheduled job: time zone of its schedule, concurrency policy, deadline to start a late execution,
 * number of successful and failed executions to keep, and suspension of its next executions. An option not defined
 * (`null`) keeps the orchestrator's default behavior
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
readonly class ScheduleOptions
{
    public function __construct(
        public ?string $timeZone = null,
        public ?ConcurrencyPolicy $concurrency = null,
        public ?int $startingDeadline = null,
        public ?int $successfulHistory = null,
        public ?int $failedHistory = null,
        public ?bool $suspend = null,
    ) {
    }
}
