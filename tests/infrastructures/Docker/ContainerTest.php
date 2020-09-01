<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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

        self::assertInstanceOf(
            ScriptWriterInterface::class,
            $container->get(ScriptWriterInterface::class)
        );
    }

    public function testBuilderInterface()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            BuilderInterface::class,
            $container->get(BuilderInterface::class)
        );
    }

    public function testBuilderWrapper()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            BuilderWrapper::class,
            $container->get(BuilderWrapper::class)
        );
    }
}
