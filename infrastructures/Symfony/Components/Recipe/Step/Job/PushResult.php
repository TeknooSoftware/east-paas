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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Job;

use DateTimeInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;
use Teknoo\East\Common\Service\DatesService;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Object\History;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

use function array_unique;
use function array_values;
use function json_encode;

/**
 * Step able to dispatch a job's result to a Symfony Messenger Bus
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PushResult implements DispatchResultInterface
{
    public function __construct(
        private readonly DatesService $dateTimeService,
        private readonly MessageBusInterface $bus,
        private readonly NormalizerInterface $normalizer,
        private readonly ErrorFactoryInterface $errorFactory,
        private readonly SerialGenerator $generator,
        private readonly bool $preferRealDate = false,
    ) {
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function sendResult(
        ManagerInterface $manager,
        string $projectId,
        string $envName,
        string $jobId,
        mixed $result,
        array $extra = [],
    ): void {
        $this->dateTimeService->passMeTheDate(
            function (DateTimeInterface $now) use ($projectId, $envName, $jobId, $result, $manager, $extra): void {
                /** @var Promise<array<string, mixed>, mixed, mixed> $promise */
                $promise = new Promise(
                    function ($normalizedResult) use ($projectId, $envName, $jobId, $manager, $now, $extra): void {
                        $history = new History(
                            previous: null,
                            message: DispatchResultInterface::class,
                            date: $now,
                            isFinal: true,
                            extra: ['result' => $normalizedResult] + $extra,
                            serialNumber: $this->generator->getNewSerialNumber(),
                        );

                        $manager->updateWorkPlan([
                            History::class => $history,
                            'historySerialized' => json_encode($history, JSON_THROW_ON_ERROR),
                        ]);

                        $this->bus->dispatch(
                            new Envelope(
                                message: new JobDone(
                                    projectId: $projectId,
                                    environment: $envName,
                                    jobId: $jobId,
                                    message: (string) json_encode($history, JSON_THROW_ON_ERROR)
                                ),
                                stamps: [
                                    new Parameter('projectId', $projectId),
                                    new Parameter('envName', $envName),
                                    new Parameter('jobId', $jobId),
                                ]
                            )
                        );
                    }
                );

                $this->normalizer->normalize(
                    $result,
                    $promise,
                    'json'
                );
            },
            $this->preferRealDate,
        );
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function __invoke(
        ManagerInterface $manager,
        EastClient $client,
        string $projectId,
        string $envName,
        string $jobId,
        mixed $result = null,
        ?Throwable $exception = null,
        array $extra = []
    ): DispatchResultInterface {
        if (empty($result)) {
            $result = [];
        }

        $failure = $this->errorFactory->buildFailureHandler($client, $manager, 500, null);

        if (null !== $exception) {
            $result = [];
            $currentException = $exception;
            do {
                $result[] = $currentException->getMessage();
            } while (null !== ($currentException = $currentException->getPrevious()));

            $result = array_values(array_unique($result));
        }

        try {
            $this->sendResult($manager, $projectId, $envName, $jobId, $result, $extra);

            if (null !== $exception) {
                $failure($exception);
                $manager->stopErrorReporting();
            }
        } catch (Throwable $throwable) {
            $failure($throwable);
        }

        return $this;
    }
}
