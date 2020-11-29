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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps;

use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Website\Service\DatesService;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DisplayHistory implements DispatchHistoryInterface
{
    private DatesService $dateTimeService;

    public function __construct(DatesService $dateTimeService)
    {
        $this->dateTimeService = $dateTimeService;
    }

    public function __invoke(
        JobUnitInterface $job,
        string $step,
        array $extra = [],
        ?OutputInterface $output = null
    ): DispatchHistoryInterface {
        if (!$output) {
           return $this;
        }

        $this->dateTimeService->passMeTheDate(
            function (\DateTimeInterface $now) use ($step, $extra, $output) {
                $history = new History(
                    null,
                    $step,
                    $now,
                    false,
                    $extra
                );

                $output->writeln((string) \json_encode($history));
            }
        );

        return $this;
    }
}