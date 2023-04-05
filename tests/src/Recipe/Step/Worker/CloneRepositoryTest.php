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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Recipe\Step\Worker\CloneRepository;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
