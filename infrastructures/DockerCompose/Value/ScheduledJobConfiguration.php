<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Value;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ConcurrencyPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\ScheduleOptions;

use function escapeshellarg;
use function implode;

/**
 * Immutable configuration of the pod of a scheduled job, pushed in `jobs/<anchor>.conf` and sourced by the runner
 * `jobs/east-paas-cron.sh`. An option not defined keeps the default of Kubernetes.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final readonly class ScheduledJobConfiguration
{
    private const int SUCCESSFUL_HISTORY = 3;

    private const int FAILED_HISTORY = 1;

    /**
     * @param list<string> $services the anchor first, then its sidecars
     */
    public function __construct(
        public string $project,
        public array $services,
        public JobRunSettings $run,
        public string $pattern,
        public ?ScheduleOptions $options = null,
    ) {
    }

    public function toShell(): string
    {
        $options = $this->options;
        $values = [
            'project' => $this->project,
            'services' => implode(' ', $this->services),
            'completions' => (string) $this->run->completions,
            'time_limit' => (string) $this->run->timeLimit,
            'ok_codes' => implode(' ', $this->run->okCodes),
            'concurrency' => ($options->concurrency ?? ConcurrencyPolicy::Allow)->value,
            'time_zone' => (string) $options?->timeZone,
            'pattern' => $this->pattern,
            'deadline' => (string) $options?->startingDeadline,
            'successful_history' => (string) ($options->successfulHistory ?? self::SUCCESSFUL_HISTORY),
            'failed_history' => (string) ($options->failedHistory ?? self::FAILED_HISTORY),
        ];

        $content = '';
        foreach ($values as $name => $value) {
            $content .= $name . '=' . escapeshellarg($value) . "\n";
        }

        return $content;
    }
}
