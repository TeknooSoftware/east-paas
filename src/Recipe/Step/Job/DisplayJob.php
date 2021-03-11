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

namespace Teknoo\East\Paas\Recipe\Step\Job;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;
use Teknoo\East\Paas\Recipe\Traits\ResponseTrait;
use Teknoo\Recipe\ChefInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DisplayJob
{
    use ResponseTrait;
    use PsrFactoryTrait;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(Job $job, ClientInterface $client, ChefInterface $chef, string $jobSerialized): self
    {
        $client->acceptResponse(
            static::buildResponse(
                $jobSerialized,
                200,
                'application/json',
                $this->responseFactory,
                $this->streamFactory
            )
        );

        $chef->finish($job);

        return $this;
    }
}
