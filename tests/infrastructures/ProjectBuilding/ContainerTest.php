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

namespace Teknoo\Tests\East\Paas\Infrastructures\ProjectBuilding;

use ArrayObject;
use DI\Container;
use DI\ContainerBuilder;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\ComposerHook;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Exception\RuntimeException;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\MakeHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\NpmHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\PipHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\SfConsoleHook;

/**
 * @license     http://teknoo.software/license/mit         MIT License
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
        $containerDefinition->addDefinitions(__DIR__ . '/../../../infrastructures/ProjectBuilding/di.php');

        return $containerDefinition->build();
    }

    public function testComposerHookDeprecated()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.composer.phar.path', '/foo/composer.phar');

        self::assertInstanceOf(
            ComposerHook::class,
            $container->get(ComposerHook::class)
        );
    }

    public function testComposerHookWithString()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.composer.path', '/foo/composer.phar');

        self::assertInstanceOf(
            ComposerHook::class,
            $container->get(ComposerHook::class)
        );
    }

    public function testComposerHookWithArray()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.composer.path', ['/foo/composer.phar']);

        self::assertInstanceOf(
            ComposerHook::class,
            $container->get(ComposerHook::class)
        );
    }

    public function testComposerHookWithArrayObjectt()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.composer.path', new ArrayObject(['/foo/composer.phar']));

        self::assertInstanceOf(
            ComposerHook::class,
            $container->get(ComposerHook::class)
        );
    }

    public function testComposerHookWithoutDIParameter()
    {
        $container = $this->buildContainer();

        $this->expectException(RuntimeException::class);
        $container->get(ComposerHook::class);
    }

    public function testComposerHookWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.composer.phar.path', '/foo/composer.phar');
        $container->set('teknoo.east.paas.composer.timeout', 240);

        self::assertInstanceOf(
            ComposerHook::class,
            $container->get(ComposerHook::class)
        );
    }

    public function testComposerHookFactory()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.composer.phar.path', '/foo/composer.phar');

        self::assertIsCallable(
            $factory= $container->get(ComposerHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testComposerHookFactoryWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.composer.phar.path', '/foo/composer.phar');
        $container->set('teknoo.east.paas.composer.timeout', 240);

        self::assertIsCallable(
            $factory = $container->get(ComposerHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testPipHook()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.pip.path', '/foo/pip');

        self::assertInstanceOf(
            PipHook::class,
            $container->get(PipHook::class)
        );
    }

    public function testPipHookWithArray()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.pip.path', ['/foo/pip']);

        self::assertInstanceOf(
            PipHook::class,
            $container->get(PipHook::class)
        );
    }

    public function testPipHookWithArrayObject()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.pip.path', new ArrayObject(['/foo/pip']));

        self::assertInstanceOf(
            PipHook::class,
            $container->get(PipHook::class)
        );
    }

    public function testPipHookWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.pip.path', '/foo/pip');
        $container->set('teknoo.east.paas.pip.timeout', 240);

        self::assertInstanceOf(
            PipHook::class,
            $container->get(PipHook::class)
        );
    }

    public function testPipHookFactory()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.pip.path', '/foo/pip');

        self::assertIsCallable(
            $factory= $container->get(PipHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testPipHookFactoryWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.pip.path', '/foo/pip');
        $container->set('teknoo.east.paas.pip.timeout', 240);

        self::assertIsCallable(
            $factory = $container->get(PipHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testNpmHook()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.npm.path', '/foo/npm');

        self::assertInstanceOf(
            NpmHook::class,
            $container->get(NpmHook::class)
        );
    }

    public function testNpmHookWithArray()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.npm.path', ['/foo/npm']);

        self::assertInstanceOf(
            NpmHook::class,
            $container->get(NpmHook::class)
        );
    }

    public function testNpmHookWithArrayObject()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.npm.path', new ArrayObject(['/foo/npm']));

        self::assertInstanceOf(
            NpmHook::class,
            $container->get(NpmHook::class)
        );
    }

    public function testNpmHookWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.npm.path', '/foo/npm');
        $container->set('teknoo.east.paas.npm.timeout', 240);

        self::assertInstanceOf(
            NpmHook::class,
            $container->get(NpmHook::class)
        );
    }

    public function testNpmHookFactory()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.npm.path', '/foo/npm');

        self::assertIsCallable(
            $factory= $container->get(NpmHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testNpmHookFactoryWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.npm.path', '/foo/npm');
        $container->set('teknoo.east.paas.npm.timeout', 240);

        self::assertIsCallable(
            $factory = $container->get(NpmHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testMakeHook()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.make.path', '/foo/make');

        self::assertInstanceOf(
            MakeHook::class,
            $container->get(MakeHook::class)
        );
    }

    public function testMakeHookWithArray()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.make.path', ['/foo/make']);

        self::assertInstanceOf(
            MakeHook::class,
            $container->get(MakeHook::class)
        );
    }

    public function testMakeHookWithArrayObject()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.make.path', new ArrayObject(['/foo/make']));

        self::assertInstanceOf(
            MakeHook::class,
            $container->get(MakeHook::class)
        );
    }

    public function testMakeHookWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.make.path', '/foo/make');
        $container->set('teknoo.east.paas.make.timeout', 240);

        self::assertInstanceOf(
            MakeHook::class,
            $container->get(MakeHook::class)
        );
    }

    public function testMakeHookFactory()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.make.path', '/foo/make');

        self::assertIsCallable(
            $factory= $container->get(MakeHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testMakeHookFactoryWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.make.path', '/foo/make');
        $container->set('teknoo.east.paas.make.timeout', 240);

        self::assertIsCallable(
            $factory = $container->get(MakeHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testSfConsoleHook()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.symfony_console.path', '/foo/sfConsole');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $container->get(SfConsoleHook::class)
        );
    }

    public function testSfConsoleHookWithArray()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.symfony_console.path', ['/foo/sfConsole']);

        self::assertInstanceOf(
            SfConsoleHook::class,
            $container->get(SfConsoleHook::class)
        );
    }

    public function testSfConsoleHookWithArrayObject()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.symfony_console.path', new ArrayObject(['/foo/sfConsole']));

        self::assertInstanceOf(
            SfConsoleHook::class,
            $container->get(SfConsoleHook::class)
        );
    }

    public function testSfConsoleHookWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.symfony_console.path', '/foo/sfConsole');
        $container->set('teknoo.east.paas.symfony_console.timeout', 240);

        self::assertInstanceOf(
            SfConsoleHook::class,
            $container->get(SfConsoleHook::class)
        );
    }

    public function testSfConsoleHookFactory()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.symfony_console.path', '/foo/sfConsole');

        self::assertIsCallable(
            $factory= $container->get(SfConsoleHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }

    public function testSfConsoleHookFactoryWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.symfony_console.path', '/foo/sfConsole');
        $container->set('teknoo.east.paas.symfony_console.timeout', 240);

        self::assertIsCallable(
            $factory = $container->get(SfConsoleHook::class . ':factory')
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], '/tmp'),
        );
    }
}
