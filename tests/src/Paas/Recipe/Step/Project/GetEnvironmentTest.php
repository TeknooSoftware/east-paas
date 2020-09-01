<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Project;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment;
use Teknoo\Recipe\ChefInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment
 */
class GetEnvironmentTest extends TestCase
{
    public function buildStep(): GetEnvironment
    {
        return new GetEnvironment();
    }

    public function testInvoke()
    {
        $chef = $this->createMock(ChefInterface::class);

        $envName = 'dev';
        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['environment' => new Environment($envName)]);

        self::assertInstanceOf(
            GetEnvironment::class,
            $this->buildStep()($envName, $chef)
        );
    }
}
