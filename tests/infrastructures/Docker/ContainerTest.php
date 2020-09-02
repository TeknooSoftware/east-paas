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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Docker;

use DI\Container;
use DI\ContainerBuilder;
use Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper;
use Teknoo\East\Paas\Infrastructures\Docker\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Docker\Contracts\ScriptWriterInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @package Teknoo\Tests\East\Paas\Infrastructures\Docker
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
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Docker/di.php');

        return $containerDefinition->build();
    }

    public function testProcessFactoryInterface()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            ProcessFactoryInterface::class,
            $container->get(ProcessFactoryInterface::class)
        );
    }

    public function testScriptWriterInterface()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');

        self::assertInstanceOf(
            ScriptWriterInterface::class,
            $container->get(ScriptWriterInterface::class)
        );
    }

    public function testBuilderInterface()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');

        self::assertInstanceOf(
            BuilderInterface::class,
            $container->get(BuilderInterface::class)
        );
    }

    public function testBuilderWrapper()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');

        self::assertInstanceOf(
            BuilderWrapper::class,
            $container->get(BuilderWrapper::class)
        );
    }
}
