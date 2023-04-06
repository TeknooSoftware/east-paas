<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\History;

use DateTimeInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Common\Service\DatesService;

use function json_encode;

/**
 * Step able to dispatch any job's event on a Symfony Messenger bus.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SendHistory implements DispatchHistoryInterface
{
    public function __construct(
        private readonly DatesService $dateTimeService,
        private readonly MessageBusInterface $bus,
        private readonly SerialGenerator $generator,
        private readonly bool $preferRealDate = false,
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
            function (DateTimeInterface $now) use ($projectId, $envName, $jobId, $step, $extra): void {
                $history = new History(
                    previous: null,
                    message: $step,
                    date: $now,
                    isFinal: false,
                    extra: $extra,
                    serialNumber: $this->generator->getNewSerialNumber(),
                );

                $this->bus->dispatch(
                    new Envelope(
                        new HistorySent(
                            $projectId,
                            $envName,
                            $jobId,
                            (string) json_encode($history, JSON_THROW_ON_ERROR)
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
