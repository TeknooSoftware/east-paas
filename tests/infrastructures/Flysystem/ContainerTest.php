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

namespace Teknoo\Tests\East\Paas\Infrastructures\Flysystem;

use DI\Container;
use DI\ContainerBuilder;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;
use League\Flysystem\Filesystem;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Flysystem/di.php');

        return $containerDefinition->build();
    }

    public function testLocal()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');

        self::assertInstanceOf(
            LocalFilesystemAdapter::class,
            $container->get(LocalFilesystemAdapter::class)
        );
    }

    public function testFilesystem()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');

        self::assertInstanceOf(
            Filesystem::class,
            $container->get(Filesystem::class)
        );
    }

    public function testJobWorkspaceInterface()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set('teknoo.east.paas.project_configuration_filename', '.paas.yaml');

        self::assertInstanceOf(
            JobWorkspaceInterface::class,
            $container->get(JobWorkspaceInterface::class)
        );
    }

    public function testJobWorkspaceFlysystem()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set('teknoo.east.paas.project_configuration_filename', '.paas.yaml');

        self::assertInstanceOf(
            Workspace::class,
            $container->get(Workspace::class)
        );
    }
}
