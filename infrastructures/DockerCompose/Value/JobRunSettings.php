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

use function array_map;
use function array_unique;
use function array_values;
use function is_array;
use function is_numeric;
use function max;

/**
 * Immutable run settings of a job pod, read from the meta `x-paas-job` of its anchor. The time limit is in seconds,
 * 0 for none.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final readonly class JobRunSettings
{
    /**
     * @param list<int> $okCodes
     */
    private function __construct(
        public int $completions,
        public int $timeLimit,
        public array $okCodes,
    ) {
    }

    /**
     * @param array<mixed> $meta
     */
    public static function fromMeta(array $meta): self
    {
        $completions = 1;
        if (is_numeric($meta['completions'] ?? null)) {
            $completions = max(1, (int) $meta['completions']);
        }

        $timeLimit = 0;
        if (is_numeric($meta['time_limit'] ?? null)) {
            $timeLimit = (int) $meta['time_limit'];
        }

        /** @var array<int, int> $okCodes */
        $okCodes = $meta['success_exit_codes'] ?? [];
        if (!is_array($okCodes) || empty($okCodes)) {
            $okCodes = [0];
        }

        return new self(
            completions: $completions,
            timeLimit: $timeLimit,
            okCodes: array_values(array_unique(array_map('intval', $okCodes))),
        );
    }
}
