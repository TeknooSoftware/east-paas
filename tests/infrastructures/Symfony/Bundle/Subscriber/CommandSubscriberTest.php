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

namespace Teknoo\Tests\East\Paas\Infrastructures\EastPaasBundle\Subscriber;


use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Teknoo\East\Paas\Infrastructures\EastPaasBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Teknoo\East\Paas\Infrastructures\EastPaasBundle\Subscriber\CommandSubscriber;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayHistory;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayResult;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\EastPaasBundle\Subscriber\CommandSubscriber
 */
class CommandSubscriberTest extends TestCase
{
    private ?DisplayHistory $stepDisplayHistory = null;

    private ?DisplayResult $stepDisplayResult = null;

    private ?ContainerInterface $container = null;

    /**
     * @return DisplayHistory|MockObject
     */
    public function getStepDisplayHistory(): ?DisplayHistory
    {
        if (!$this->stepDisplayHistory instanceof DisplayHistory) {
            $this->stepDisplayHistory = $this->createMock(DisplayHistory::class);
        }

        return $this->stepDisplayHistory;
    }

    /**
     * @return DisplayResult|MockObject
     */
    public function getStepDisplayResult(): ?DisplayResult
    {
        if (!$this->stepDisplayResult instanceof DisplayResult) {
            $this->stepDisplayResult = $this->createMock(DisplayResult::class);
        }

        return $this->stepDisplayResult;
    }

    /**
     * @return ContainerInterface|MockObject
     */
    public function getContainer(): ?ContainerInterface
    {
        if (!$this->container instanceof ContainerInterface) {
            $this->container = $this->createMock(ContainerInterface::class);
        }

        return $this->container;
    }

    /**
     * @return CommandSubscriber
     */
    private function buildConfiguration(): CommandSubscriber
    {
        return new CommandSubscriber(
            $this->getStepDisplayHistory(),
            $this->getStepDisplayResult(),
            $this->getContainer()
        );
    }

    public function testGetSubscribedEvents()
    {
        self::assertIsArray(
            CommandSubscriber::getSubscribedEvents()
        );
    }

    public function testUpdateContainer()
    {
        self::assertInstanceOf(
            CommandSubscriber::class,
            $this->buildConfiguration()->updateContainer()
        );
    }
}
