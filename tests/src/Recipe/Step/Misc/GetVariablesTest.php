<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Misc;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Recipe\Step\Misc\GetVariables;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Misc\GetVariables
 */
class GetVariablesTest extends TestCase
{
    public function buildStep(): GetVariables
    {
        return new GetVariables();
    }

    public function testInvokeNotJson()
    {
        $chef = $this->createMock(ManagerInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $message->expects(self::any())->method('getHeader')->willReturn(['html']);

        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['envVars' => []]);

        self::assertInstanceOf(
            GetVariables::class,
            $this->buildStep()($chef, $message, $client)
        );
    }

    public function testInvokeJson()
    {
        $chef = $this->createMock(ManagerInterface::class);
        $message = $this->createMock(MessageInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $message->expects(self::any())->method('getHeader')->willReturn(['application/json']);
        $message->expects(self::any())->method('getBody')->willReturn(
            \json_encode($data = ['foo' => 'bar'])
        );

        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['envVars' => $data]);

        self::assertInstanceOf(
            GetVariables::class,
            $this->buildStep()($chef, $message, $client)
        );
    }
}
