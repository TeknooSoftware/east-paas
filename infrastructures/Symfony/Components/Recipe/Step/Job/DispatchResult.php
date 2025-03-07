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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Job;

use DateTimeInterface;
use SensitiveParameter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Teknoo\East\Foundation\Client\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DispatchResult implements DispatchResultInterface
{
    public function __construct(
        private readonly DatesService $dateTimeService,
        private readonly MessageBusInterface $bus,
        private readonly NormalizerInterface $normalizer,
        private readonly ErrorFactoryInterface $errorFactory,
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
        return fn (SensitiveContentInterface $message) => $this->bus->dispatch(
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

                        $dispatching = $this->buildDispatching(
                            projectId: $projectId,
                            envName: $envName,
                            jobId: $jobId,
                        );

                        $message = new JobDone(
                            projectId: $projectId,
                            environment: $envName,
                            jobId: $jobId,
                            message: (string) json_encode($history, JSON_THROW_ON_ERROR)
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
        #[SensitiveParameter] mixed $result = null,
        ?Throwable $exception = null,
        #[SensitiveParameter] array $extra = []
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
