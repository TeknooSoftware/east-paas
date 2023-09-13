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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

use function str_replace;

/**
 * Message handler for Symfony Messenger to handle a HistorySent and forward it to a remmote server via a HTTP request.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

#[AsMessageHandler]
class HistorySentHandler implements HistorySentHandlerInterface
{
    use RequestTrait;

    public function __construct(
        private readonly string $urlPattern,
        private readonly string $method,
        private ?EncryptionInterface $encryption,
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

    public function __invoke(HistorySent $historySent): HistorySentHandlerInterface
    {
        $processMessage = function (HistorySent $historySent): void {
            $url = str_replace(
                ['{projectId}', '{envName}', '{jobId}'],
                [$historySent->getProjectId(), $historySent->getEnvironment(), $historySent->getJobId()],
                $this->urlPattern
            );

            $this->sendRequest(
                $this->method,
                $url,
                'application/json',
                $historySent->getMessage()
            );
        };

        if (null !== $this->encryption) {
            /** @var Promise<HistorySent, mixed, mixed> $promise */
            $promise = new Promise(
                onSuccess: $processMessage,
                onFail: fn (Throwable $error) => throw $error,
            );

            $this->encryption->decrypt(
                $historySent,
                $promise,
            );
        } else {
            $processMessage($historySent);
        }

        return $this;
    }
}
