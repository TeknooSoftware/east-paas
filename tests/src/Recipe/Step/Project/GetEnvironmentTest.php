<?php

declare(strict_types=1);

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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Project;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
        $manager = $this->createMock(ManagerInterface::class);

        $envName = 'dev';
        $manager->expects(self::once())
            ->method('updateWorkPlan')
            ->with([Environment::class => new Environment($envName)]);

        self::assertInstanceOf(
            GetEnvironment::class,
            $this->buildStep()($envName, $manager)
        );
    }
}
