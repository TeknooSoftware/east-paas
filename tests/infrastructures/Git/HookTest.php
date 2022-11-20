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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Git;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Git\Hook
 * @covers \Teknoo\East\Paas\Infrastructures\Git\Hook\Generator
 * @covers \Teknoo\East\Paas\Infrastructures\Git\Hook\Running
 */
class HookTest extends TestCase
{
    /**
     * @var Process
     */
    private $process;

    public function getProcessMock(bool $isSuccessFull = true): MockObject&Process
    {
        if (!$this->process instanceof Process) {
            $this->process = $this->createMock(Process::class);

            $this->process
                ->expects(self::any())
                ->method('isSuccessFul')
                ->willReturn($isSuccessFull);
        }

        return $this->process;
    }

    /**
     * @return Hook
     */
    public function buildHook(): Hook
    {
        return new Hook(
            $this->getProcessMock(),
            'private.key',
        );
    }

    public function testSetContextBadJobUnit()
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setContext(
            new \stdClass(),
            $this->createMock(JobWorkspaceInterface::class)
        );
    }

    public function testSetContextBadWorkspace()
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setContext(
            $this->createMock(JobUnitInterface::class),
            new \stdClass()
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
        $this->expectException(\TypeError::class);
        $this->buildHook()->setPath(new \stdClass());
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
        $this->expectException(\TypeError::class);
        $this->buildHook()->setOptions(new \stdClass(), $this->createMock(PromiseInterface::class));
    }

    public function testSetOptionsBadPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setOptions([], new \stdClass());
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
        $this->expectException(\TypeError::class);
        $this->buildHook()->run(new \stdClass());
    }

    public function testRunWithSsh()
    {
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects(self::once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace) {
                self::assertEquals('private.key', $file->getName());
                self::assertEquals('fooBar', $file->getContent());
                self::assertEquals(Visibility::Private, $file->getVisibility());

                $return();

                return $workspace;
            });

        $workspace->expects(self::once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $callback) use ($workspace) {
                $callback('foo', 'bar');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects(self::once())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects(self::once())
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
        $promise->expects(self::never())->method('fail');
        $promise->expects(self::once())->method('success');

        self::assertInstanceOf(
            Hook::class,
            $hook->run($promise)
        );
    }

    public function testRunWithHttp()
    {
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects(self::never())
            ->method('writeFile');

        $workspace->expects(self::never())
            ->method('runInRepositoryPath');

        $this->getProcessMock()
            ->expects(self::never())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects(self::never())
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
        $promise->expects(self::once())->method('fail');
        $promise->expects(self::never())->method('success');

        self::assertInstanceOf(
            Hook::class,
            $hook->run($promise)
        );
    }

    public function testRunWithHttps()
    {
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects(self::never())
            ->method('writeFile');

        $workspace->expects(self::once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $callback) use ($workspace) {
                $callback('foo', 'bar');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects(self::once())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects(self::once())
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
        $promise->expects(self::never())->method('fail');
        $promise->expects(self::once())->method('success');

        self::assertInstanceOf(
            Hook::class,
            $hook->run($promise)
        );
    }

    public function testRunWithError()
    {
        $this->getProcessMock(false);
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects(self::once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace) {
                self::assertEquals('private.key', $file->getName());
                self::assertEquals('fooBar', $file->getContent());
                self::assertEquals(Visibility::Private, $file->getVisibility());

                $return();

                return $workspace;
            });

        $workspace->expects(self::once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $callback) use ($workspace) {
                $callback('foo', 'bar');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects(self::once())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects(self::once())
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
        $promise->expects(self::once())->method('fail');
        $promise->expects(self::never())->method('success');

        self::assertInstanceOf(
            Hook::class,
            $hook->run($promise)
        );
    }

    public function testClone()
    {
        $hook = $this->buildHook();
        $hook2 = clone $hook;

        $rp = new \ReflectionProperty(Hook::class, 'gitProcess');
        $rp->setAccessible(true);
        self::assertNotSame($this->getProcessMock(), $rp->getValue($hook2));
        self::assertSame($this->getProcessMock(), $rp->getValue($hook));
    }
}
