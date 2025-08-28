<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Recipe\Step\Worker\CloneRepository;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(CloneRepository::class)]
class CloneRepositoryTest extends TestCase
{
    public function buildStep(): CloneRepository
    {
        return new CloneRepository();
    }

    public function testInvokeBadCloningAgent(): void
    {
        $this->expectException(TypeError::class);
        ($this->buildStep())(new stdClass());
    }

    public function testInvoke(): void
    {
        $cloningAgent = $this->createMock(CloningAgentInterface::class);
        $cloningAgent->expects($this->once())
            ->method('run')
            ->willReturnSelf();

        $this->assertInstanceOf(CloneRepository::class, ($this->buildStep())($cloningAgent));
    }
}
