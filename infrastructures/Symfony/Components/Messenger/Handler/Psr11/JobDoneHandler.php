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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\JobDoneHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;

use function str_replace;

/**
 * Message handler for Symfony Messenger to handle a JobDone and forward it to a remmote server via a HTTP request.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class JobDoneHandler implements JobDoneHandlerInterface, MessageHandlerInterface
{
    use RequestTrait;

    public function __construct(
        private string $urlPattern,
        private string $method,
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ClientInterface $client,
    ) {
        $this->uriFactory = $uriFactory;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->client = $client;
    }

    public function __invoke(JobDone $jobDone): JobDoneHandlerInterface
    {
        $url = str_replace(
            ['{projectId}','{envName}','{jobId}'],
            [$jobDone->getProjectId(), $jobDone->getEnvironment(), $jobDone->getJobId()],
            $this->urlPattern
        );

        $this->sendRequest(
            $this->method,
            $url,
            'application/json',
            $jobDone->getMessage()
        );

        return $this;
    }
}
