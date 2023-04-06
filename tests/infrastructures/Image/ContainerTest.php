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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Image;

use DI\Container;
use DI\ContainerBuilder;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\Image\ImageWrapper;
use Teknoo\East\Paas\Infrastructures\Image\Contracts\ProcessFactoryInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @package Teknoo\Tests\East\Paas\Infrastructures\Image
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
        $containerDefinition->addDefinitions(__DIR__ . '/../../../infrastructures/Image/di.php');

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
        $container->set('teknoo.east.paas.img_builder.cmd', 'docker');
        $container->set('teknoo.east.paas.img_builder.build.platforms', 'bar');

        self::assertInstanceOf(
            BuilderInterface::class,
            $container->get(BuilderInterface::class)
        );
    }

    public function testImageWrapper()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.img_builder.build.platforms', 'bar');

        self::assertInstanceOf(
            ImageWrapper::class,
            $container->get(ImageWrapper::class)
        );
    }

    public function testImageWrapperWithTileout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/foo');
        $container->set('teknoo.east.paas.img_builder.build.platforms', 'bar');
        $container->set('teknoo.east.paas.img_builder.build.timeout', 123);

        self::assertInstanceOf(
            ImageWrapper::class,
            $container->get(ImageWrapper::class)
        );
    }
}
