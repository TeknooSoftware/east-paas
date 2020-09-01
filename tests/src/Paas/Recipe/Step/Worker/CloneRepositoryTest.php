<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Recipe\Step\Worker\CloneRepository;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Worker\CloneRepository
 */
class CloneRepositoryTest extends TestCase
{
    public function buildStep(): CloneRepository
    {
        return new CloneRepository();
    }

    public function testInvokeBadCloningAgent()
    {
        $this->expectException(\TypeError::class);
        ($this->buildStep())(new \stdClass());
    }

    public function testInvoke()
    {
        $cloningAgent = $this->createMock(CloningAgentInterface::class);
        $cloningAgent->expects(self::once())
            ->method('run')
            ->willReturnSelf();

        self::assertInstanceOf(
            CloneRepository::class,
            ($this->buildStep())($cloningAgent)
        );
    }
}
