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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Misc;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Http\ClientInterface as EastClient;
use Teknoo\East\Website\Service\DatesService;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;
use Teknoo\East\Paas\Recipe\Traits\RequestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class PushResult
{
    use ErrorTrait;
    use PsrFactoryTrait;
    use RequestTrait;

    private DatesService $dateTimeService;

    private string $historyEndPoint;

    private NormalizerInterface $normalizer;

    public function __construct(
        DatesService $dateTimeService,
        string $historyEndPoint,
        NormalizerInterface $normalizer,
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ClientInterface $client,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->dateTimeService = $dateTimeService;
        $this->historyEndPoint = $historyEndPoint;
        $this->normalizer = $normalizer;
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
        $this->uriFactory = $uriFactory;
        $this->requestFactory = $requestFactory;
        $this->client = $client;
    }

    /**
     * @param mixed $result
     */
    private function sendResult(ManagerInterface $manager, JobUnitInterface $job, $result): void
    {
        $job->prepareUrl($this->historyEndPoint, new Promise(
            function ($url) use ($manager, $result) {
                $this->dateTimeService->passMeTheDate(
                    function (\DateTimeInterface $now) use ($result, $manager, $url) {
                        $this->normalizer->normalize(
                            $result,
                            new Promise(
                                function ($extra) use ($manager, $now, &$url) {
                                    $history = new History(
                                        null,
                                        static::class,
                                        $now,
                                        true,
                                        $extra
                                    );

                                    $manager->updateWorkPlan([
                                        History::class => $history,
                                        'historySerialized' => \json_encode($history),
                                    ]);

                                    $this->sendRequest(
                                        'PUT',
                                        $url,
                                        'application/json',
                                        (string) \json_encode($history)
                                    );
                                }
                            ),
                            'json'
                        );
                    }
                );
            }
        ));
    }

    /**
     * @param mixed $result
     * @param ?\Throwable $exception
     */
    public function __invoke(
        ManagerInterface $manager,
        EastClient $client,
        JobUnitInterface $job,
        $result = null,
        ?\Throwable $exception = null
    ): PushResult {
        if (empty($result)) {
            $result = [];
        }

        try {
            $this->sendResult($manager, $job, $result);
        } catch (\Throwable $error) {
            $errorCode = $error->getCode();
            if ($errorCode < 400 || $errorCode > 600) {
                $errorCode = 500;
            }

            $client->acceptResponse(
                self::buildResponse(
                    (string) \json_encode(
                        [
                            'error' => true,
                            'message' => $error->getMessage(),
                            'extra' => $error->getMessage()
                        ]
                    ),
                    $errorCode,
                    'application/json',
                    $this->responseFactory,
                    $this->streamFactory
                )
            );

            $manager->finish($error);
        }

        return $this;
    }
}
