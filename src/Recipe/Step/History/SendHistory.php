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

namespace Teknoo\East\Paas\Recipe\Step\History;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Client\ClientInterface;
use Teknoo\East\Website\Service\DatesService;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Recipe\Traits\RequestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SendHistory
{
    use RequestTrait;

    private DatesService $dateTimeService;

    private string $historyEndPoint;

    public function __construct(
        DatesService $dateTimeService,
        string $historyEndPoint,
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ClientInterface $client
    ) {
        $this->dateTimeService = $dateTimeService;
        $this->historyEndPoint = $historyEndPoint;
        $this->uriFactory = $uriFactory;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->client = $client;
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function sendStep(JobUnitInterface $job, string $step, array $extra): void
    {
        $job->prepareUrl($this->historyEndPoint, new Promise(
            function ($url) use ($step, $extra) {
                $this->dateTimeService->passMeTheDate(
                    function (\DateTimeInterface $now) use ($step, $extra, $url) {
                        $history = new History(
                            null,
                            $step,
                            $now,
                            false,
                            $extra
                        );

                        $this->sendRequest(
                            'PUT',
                            $url,
                            'application/json',
                            (string) \json_encode($history)
                        );
                    }
                );
            }
        ));
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function __invoke(JobUnitInterface $job, string $step, array $extra = []): self
    {
        $this->sendStep($job, $step, $extra);

        return $this;
    }
}
