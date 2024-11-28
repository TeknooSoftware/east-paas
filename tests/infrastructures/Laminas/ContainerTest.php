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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Laminas/di.php');

        return $containerDefinition->build();
    }

    public function testErrorFactory()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            ErrorFactory::class,
            $container->get(ErrorFactory::class)
        );

        self::assertInstanceOf(
            ErrorFactoryInterface::class,
            $container->get(ErrorFactoryInterface::class)
        );
    }

    public function testSendHistory()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            SendHistory::class,
            $container->get(SendHistory::class)
        );

        self::assertInstanceOf(
            SendHistoryInterface::class,
            $container->get(SendHistoryInterface::class)
        );
    }

    public function testSendJob()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            SendJob::class,
            $container->get(SendJob::class)
        );

        self::assertInstanceOf(
            SendJobInterface::class,
            $container->get(SendJobInterface::class)
        );
    }
}
