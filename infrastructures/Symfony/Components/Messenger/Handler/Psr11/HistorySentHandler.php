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
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;

use function str_replace;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HistorySentHandler implements HistorySentHandlerInterface, MessageHandlerInterface
{
    use RequestTrait;

    private string $urlPattern;

    private string $method;

    public function __construct(
        string $urlPattern,
        string $method,
        UriFactoryInterface $uriFactory,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ClientInterface $client
    ) {
        $this->urlPattern = $urlPattern;
        $this->method = $method;
        $this->uriFactory = $uriFactory;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->client = $client;
    }

    public function __invoke(HistorySent $historySent): HistorySentHandlerInterface
    {
        $url = str_replace(
            ['{projectId}','{envName}','{jobId}'],
            [$historySent->getProjectId(), $historySent->getEnvironment(), $historySent->getJobId()],
            $this->urlPattern
        );

        $this->sendRequest(
            $this->method,
            $url,
            'application/json',
            $historySent->getMessage()
        );

        return $this;
    }
}
