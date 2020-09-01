<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Misc;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Recipe\Step\Misc\GetVariables;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Misc\GetVariables
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
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
        $request = $this->createMock(ServerRequestInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $request->expects(self::any())->method('getHeader')->willReturn(['html']);

        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['envVars' => []]);

        self::assertInstanceOf(
            GetVariables::class,
            $this->buildStep()($chef, $request, $client)
        );
    }

    public function testInvokeJson()
    {
        $chef = $this->createMock(ManagerInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $request->expects(self::any())->method('getHeader')->willReturn(['application/json']);
        $request->expects(self::any())->method('getBody')->willReturn(
            \json_encode($data = ['foo' => 'bar'])
        );

        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['envVars' => $data]);

        self::assertInstanceOf(
            GetVariables::class,
            $this->buildStep()($chef, $request, $client)
        );
    }
}
