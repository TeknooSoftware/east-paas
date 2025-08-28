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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\History;

use DateTimeInterface;
use SensitiveParameter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Object\History;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

use function json_encode;

/**
 * Step able to dispatch any job's event on a Symfony Messenger bus.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DispatchHistory implements DispatchHistoryInterface
{
    public function __construct(
        private readonly DatesService $dateTimeService,
        private readonly MessageBusInterface $bus,
        private readonly SerialGenerator $generator,
        private readonly bool $preferRealDate = false,
        private readonly ?EncryptionInterface $encryption = null,
    ) {
    }

    private function buildDispatching(
        string $projectId,
        string $envName,
        string $jobId,
    ): callable {
        return fn (SensitiveContentInterface $message): Envelope => $this->bus->dispatch(
            new Envelope(
                message: $message,
                stamps: [
                    new Parameter('projectId', $projectId),
                    new Parameter('envName', $envName),
                    new Parameter('jobId', $jobId),
                ]
            )
        );
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

                $dispatching = $this->buildDispatching(
                    projectId: $projectId,
                    envName: $envName,
                    jobId: $jobId,
                );

                $message = new HistorySent(
                    projectId: $projectId,
                    environment: $envName,
                    jobId: $jobId,
                    message: json_encode($history, JSON_THROW_ON_ERROR)
                );

                if (null === $this->encryption) {
                    $dispatching($message);
                } else {
                    /** @var Promise<SensitiveContentInterface, mixed, mixed> $promise */
                    $promise = new Promise(
                        onSuccess: $dispatching,
                        onFail: fn (#[SensitiveParameter] Throwable $error) => throw $error,
                    );

                    $this->encryption->encrypt(
                        data: $message,
                        promise: $promise,
                    );
                }
            },
            $this->preferRealDate,
        );
    }

    /**
     * @param array<string, mixed> $historyExtra
     */
    public function __invoke(
        string $projectId,
        string $envName,
        string $jobId,
        string $step,
        #[SensitiveParameter] array $historyExtra = []
    ): DispatchHistoryInterface {
        $this->sendStep($projectId, $envName, $jobId, $step, $historyExtra);

        return $this;
    }
}
