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

namespace Teknoo\Tests\East\Paas\Infrastructures\Laminas;

use DI\Container;
use DI\ContainerBuilder;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\Job\SendJob;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\ErrorFactory;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @throws \Exception
     */
    protected function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Laminas/di.php');

        return $containerDefinition->build();
    }

    public function testErrorFactory(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(ErrorFactory::class, $container->get(ErrorFactory::class));

        $this->assertInstanceOf(ErrorFactoryInterface::class, $container->get(ErrorFactoryInterface::class));
    }

    public function testSendHistory(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(SendHistory::class, $container->get(SendHistory::class));

        $this->assertInstanceOf(SendHistoryInterface::class, $container->get(SendHistoryInterface::class));
    }

    public function testSendJob(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(SendJob::class, $container->get(SendJob::class));

        $this->assertInstanceOf(SendJobInterface::class, $container->get(SendJobInterface::class));
    }
}
