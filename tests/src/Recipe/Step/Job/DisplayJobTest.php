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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\DisplayJob;
use Teknoo\Recipe\ChefInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\DisplayJob
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\ResponseTrait
 */
class DisplayJobTest extends TestCase
{
    public function buildStep(): DisplayJob
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();
        $response->expects(self::any())->method('withStatus')->willReturnSelf();

        $messageFactory = $this->createMock(MessageFactoryInterface::class);
        $messageFactory->expects(self::any())->method('createMessage')->willReturn(
            $response
        );

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->expects(self::any())->method('createStream')->willReturn(
            $this->createMock(StreamInterface::class)
        );

        return new DisplayJob(
            $messageFactory,
            $streamFactory
        );
    }

    public function testInvoke()
    {
        $chef = $this->createMock(ChefInterface::class);
        $job = $this->createMock(Job::class);
        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::once())
            ->method('acceptResponse')
            ->with(self::callback(function ($response) {
                return $response instanceof ResponseInterface;
            }));

        $chef->expects(self::once())
            ->method('finish')
            ->with($job);

        self::assertInstanceOf(
            DisplayJob::class,
            $this->buildStep()($job, $client, $chef, 'fooBar')
        );
    }
}
