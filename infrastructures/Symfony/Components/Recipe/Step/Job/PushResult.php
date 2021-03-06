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
use Teknoo\East\Website\Service\DatesService;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Foundation\Promise\Promise;
use Throwable;

use function json_encode;

/**
 * Step able to dispatch a job's result to a Symfony Messenger Bus
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class PushResult implements DispatchResultInterface
{
    public function __construct(
        private DatesService $dateTimeService,
        private MessageBusInterface $bus,
        private NormalizerInterface $normalizer,
        private ErrorFactoryInterface $errorFactory,
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
            function (DateTimeInterface $now) use ($projectId, $envName, $jobId, $result, $manager, $extra) {
                $this->normalizer->normalize(
                    $result,
                    new Promise(
                        function ($normalizedResult) use ($projectId, $envName, $jobId, $manager, $now, $extra) {
                            $history = new History(
                                null,
                                DispatchResultInterface::class,
                                $now,
                                true,
                                ['result' => $normalizedResult] + $extra
                            );

                            $manager->updateWorkPlan([
                                History::class => $history,
                                'historySerialized' => json_encode($history),
                            ]);

                            $this->bus->dispatch(
                                new Envelope(
                                    new JobDone(
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
                        }
                    ),
                    'json'
                );
            }
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

        try {
            $this->sendResult($manager, $projectId, $envName, $jobId, $result, $extra);
        } catch (Throwable $error) {
            $errorCode = $error->getCode();
            if ($errorCode < 400 || $errorCode > 600) {
                $errorCode = 500;
            }

            $this->errorFactory
                ->buildFailurePromise($client, $manager, $errorCode, null)($error);
        }

        return $this;
    }
}
