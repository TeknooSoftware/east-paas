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

namespace Teknoo\Tests\East\Paas\Infrastructures\Git;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use stdClass;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\East\Paas\Infrastructures\Git\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook\Generator;
use Teknoo\East\Paas\Infrastructures\Git\Hook\Running;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use TypeError;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Running::class)]
#[CoversClass(Generator::class)]
#[CoversClass(Hook::class)]
class HookTest extends TestCase
{
    /**
     * @var ProcessFactoryInterface
     */
    private $processFactory;

    /**
     * @var Process
     */
    private $process;

    public function getProcessFactoryMock(bool $isSuccessFull = true): MockObject&ProcessFactoryInterface
    {
        if (!$this->processFactory instanceof ProcessFactoryInterface) {
            $this->processFactory = $this->createMock(ProcessFactoryInterface::class);
            $this->processFactory
                ->expects($this->any())
                ->method('__invoke')
                ->willReturn($this->getProcessMock());

            $this->getProcessMock()
                ->expects($this->any())
                ->method('isSuccessFul')
                ->willReturn($isSuccessFull);
        }

        return $this->processFactory;
    }

    public function getProcessMock(): MockObject&Process
    {
        if (!$this->process instanceof Process) {
            $this->process = $this->createMock(Process::class);
        }

        return $this->process;
    }

    /**
     * @return Hook
     */
    public function buildHook(): Hook
    {
        return new Hook(
            $this->getProcessFactoryMock(),
            'private.key',
        );
    }

    public function testSetContextBadJobUnit()
    {
        $this->expectException(TypeError::class);
        $this->buildHook()->setContext(
            new stdClass(),
            $this->createMock(JobWorkspaceInterface::class)
        );
    }

    public function testSetContextBadWorkspace()
    {
        $this->expectException(TypeError::class);
        $this->buildHook()->setContext(
            $this->createMock(JobUnitInterface::class),
            new stdClass()
        );
    }

    public function testSetContext()
    {
        self::assertInstanceOf(
            Hook::class,
            $this->buildHook()->setContext(
                $this->createMock(JobUnitInterface::class),
                $this->createMock(JobWorkspaceInterface::class)
            )
        );
    }

    public function testSetPathBadPath()
    {
        $this->expectException(TypeError::class);
        $this->buildHook()->setPath(new stdClass());
    }

    public function testSetPath()
    {
        self::assertInstanceOf(
            Hook::class,
            $this->buildHook()->setPath('/foo')
        );
    }

    public function testSetOptionsBadOptions()
    {
        $this->expectException(TypeError::class);
        $this->buildHook()->setOptions(new stdClass(), $this->createMock(PromiseInterface::class));
    }

    public function testSetOptionsBadPromise()
    {
        $this->expectException(TypeError::class);
        $this->buildHook()->setOptions([], new stdClass());
    }
    
    public function testSetOptions()
    {
        $promise = $this->createMock(PromiseInterface::class);

        self::assertInstanceOf(
            Hook::class,
            $this->buildHook()->setOptions(['foo' => 'bar'], $promise)
        );
    }

    public function testRunBadPromise()
    {
        $this->expectException(TypeError::class);
        $this->buildHook()->run(new stdClass());
    }

    public function testRunWithSsh()
    {
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects($this->once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace) {
                self::assertEquals('private.key', $file->getName());
                self::assertEquals('fooBar', $file->getContent());
                self::assertEquals(Visibility::Private, $file->getVisibility());

                $return();

                return $workspace;
            });

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $callback) use ($workspace) {
                $callback('foo', 'bar');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects($this->once())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects($this->once())
            ->method('run');

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setOptions(
                [
                    'url' => 'git@bar:foo',
                    'key' => 'fooBar',
                    'path' => '/bar'
                ],
                $this->createMock(PromiseInterface::class)
            )
        );

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setPath('foo')
        );

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setContext(
                $this->createMock(JobUnitInterface::class),
                $workspace
            )
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('fail');
        $promise->expects($this->once())->method('success');

        self::assertInstanceOf(
            Hook::class,
            $hook->run($promise)
        );
    }

    public function testRunWithHttp()
    {
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects($this->never())
            ->method('writeFile');

        $workspace->expects($this->never())
            ->method('runInRepositoryPath');

        $this->getProcessMock()
            ->expects($this->never())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects($this->never())
            ->method('run');

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setOptions(
                [
                    'url' => 'http://foo.bar',
                    'path' => '/bar'
                ],
                $this->createMock(PromiseInterface::class)
            )
        );

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setPath('foo')
        );

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setContext(
                $this->createMock(JobUnitInterface::class),
                $workspace
            )
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');
        $promise->expects($this->never())->method('success');

        self::assertInstanceOf(
            Hook::class,
            $hook->run($promise)
        );
    }

    public function testRunWithHttps()
    {
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects($this->never())
            ->method('writeFile');

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $callback) use ($workspace) {
                $callback('foo', 'bar');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects($this->once())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects($this->once())
            ->method('run');

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setOptions(
                [
                    'url' => 'https://bar.foo',
                    'path' => '/bar'
                ],
                $this->createMock(PromiseInterface::class)
            )
        );

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setPath('foo')
        );

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setContext(
                $this->createMock(JobUnitInterface::class),
                $workspace
            )
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('fail');
        $promise->expects($this->once())->method('success');

        self::assertInstanceOf(
            Hook::class,
            $hook->run($promise)
        );
    }

    public function testRunWithError()
    {
        $this->getProcessFactoryMock(false);
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects($this->once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace) {
                self::assertEquals('private.key', $file->getName());
                self::assertEquals('fooBar', $file->getContent());
                self::assertEquals(Visibility::Private, $file->getVisibility());

                $return();

                return $workspace;
            });

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $callback) use ($workspace) {
                $callback('foo', 'bar');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects($this->once())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects($this->once())
            ->method('run');

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setOptions(
                [
                    'url' => 'git@bar:foo',
                    'key' => 'fooBar',
                    'path' => '/bar'
                ],
                $this->createMock(PromiseInterface::class)
            )
        );

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setPath('foo')
        );

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setContext(
                $this->createMock(JobUnitInterface::class),
                $workspace
            )
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');
        $promise->expects($this->never())->method('success');

        self::assertInstanceOf(
            Hook::class,
            $hook->run($promise)
        );
    }

    public function testClone()
    {
        $hook = $this->buildHook();
        $hook2 = clone $hook;

        self::assertNotSame($hook, $hook2);
    }
}
