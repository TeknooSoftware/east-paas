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

namespace Teknoo\Tests\East\Paas\Infrastructures\BuildKit;

use DI\Container;
use DI\ContainerBuilder;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\BuildKit\BuilderWrapper;
use Teknoo\East\Paas\Infrastructures\BuildKit\Contracts\ProcessFactoryInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @package Teknoo\Tests\East\Paas\Infrastructures\BuildKit
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
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/BuildKit/di.php');

        return $containerDefinition->build();
    }

    public function testProcessFactoryInterface()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            ProcessFactoryInterface::class,
            $factory = $container->get(ProcessFactoryInterface::class)
        );

        self::assertInstanceOf(
            Process::class,
            $factory('foo')
        );
    }

    public function testBuilderInterface()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.buildkit.builder.name', 'foo');
        $container->set('teknoo.east.paas.buildkit.build.platforms', 'bar');

        self::assertInstanceOf(
            BuilderInterface::class,
            $container->get(BuilderInterface::class)
        );
    }

    public function testBuilderWrapper()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.buildkit.builder.name', 'foo');
        $container->set('teknoo.east.paas.buildkit.build.platforms', 'bar');

        self::assertInstanceOf(
            BuilderWrapper::class,
            $container->get(BuilderWrapper::class)
        );
    }

    public function testBuilderWrapperWithTileout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.buildkit.builder.name', 'foo');
        $container->set('teknoo.east.paas.buildkit.build.platforms', 'bar');
        $container->set('teknoo.east.paas.buildkit.build.timeout', 123);

        self::assertInstanceOf(
            BuilderWrapper::class,
            $container->get(BuilderWrapper::class)
        );
    }
}
