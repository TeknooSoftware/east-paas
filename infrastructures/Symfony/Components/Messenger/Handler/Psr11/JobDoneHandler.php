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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use SensitiveParameter;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\JobDoneHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

use function str_replace;

/**
 * Message handler for Symfony Messenger to handle a JobDone and forward it to a remmote server via a HTTP request.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[AsMessageHandler]
class JobDoneHandler implements JobDoneHandlerInterface
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

    public function __invoke(JobDone $jobDone): JobDoneHandlerInterface
    {
        $processMessage = function (JobDone $jobDone): void {
            $url = str_replace(
                ['{projectId}', '{envName}', '{jobId}'],
                [$jobDone->getProjectId(), $jobDone->getEnvironment(), $jobDone->getJobId()],
                $this->urlPattern
            );

            $this->sendRequest(
                $this->method,
                $url,
                'application/json',
                $jobDone->getMessage()
            );
        };

        if (null !== $this->encryption) {
            /** @var Promise<JobDone, mixed, mixed> $promise */
            $promise = new Promise(
                onSuccess: $processMessage,
                onFail: fn (#[SensitiveParameter] Throwable $error) => throw $error,
            );

            $this->encryption->decrypt(
                $jobDone,
                $promise,
            );
        } else {
            $processMessage($jobDone);
        }

        return $this;
    }
}
