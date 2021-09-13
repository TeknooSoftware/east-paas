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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\History;

use DateTimeInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Website\Service\DatesService;

use function json_encode;

/**
 * Step able to dispatch any job's event on a Symfony Messenger bus.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SendHistory implements DispatchHistoryInterface
{
    public function __construct(
        private DatesService $dateTimeService,
        private MessageBusInterface $bus,
        private bool $preferRealDate = false,
    ) {
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function sendStep(
        string $projectId,
        string $envName,
        string $jobId,
        string $step,
        array $extra
    ): void {
        $this->dateTimeService->passMeTheDate(
            function (DateTimeInterface $now) use ($projectId, $envName, $jobId, $step, $extra) {
                $history = new History(
                    null,
                    $step,
                    $now,
                    false,
                    $extra
                );

                $this->bus->dispatch(
                    new Envelope(
                        new HistorySent(
                            $projectId,
                            $envName,
                            $jobId,
                            (string) json_encode($history)
                        ),
                        [
                            new Parameter('projectId', $projectId),
                            new Parameter('envName', $envName),
                            new Parameter('jobId', $jobId)
                        ]
                    )
                );
            },
            $this->preferRealDate,
        );
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function __invoke(
        string $projectId,
        string $envName,
        string $jobId,
        string $step,
        array $extra = []
    ): DispatchHistoryInterface {
        $this->sendStep($projectId, $envName, $jobId, $step, $extra);

        return $this;
    }
}
