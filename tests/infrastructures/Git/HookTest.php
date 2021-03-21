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

namespace Teknoo\Tests\East\Paas\Infrastructures\Git;

use GitWrapper\GitWrapper;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\SshIdentity;
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
     * @var GitWrapper
     */
    private $gitWrapper;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|GitWrapper
     */
    public function getGitWrapperMock()
    {
        if (!$this->gitWrapper instanceof \PHPUnit\Framework\MockObject\MockObject) {
            $this->gitWrapper = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['setPrivateKey', 'cloneRepository'])
                ->getMock();
        }

        return $this->gitWrapper;
    }

    /**
     * @return Hook
     */
    public function buildHook(): Hook
    {
        return new Hook($this->getGitWrapperMock());
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

    public function testRun()
    {
        $hook = $this->buildHook();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects(self::once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace) {
                self::assertEquals('private.key', $file->getName());
                self::assertEquals('fooBar', $file->getContent());
                self::assertEquals(FileInterface::VISIBILITY_PRIVATE, $file->getVisibility());
                
                $return('/foo/bar/private.key');

                return $workspace;
            });

        $workspace->expects(self::once())
            ->method('runInRoot')
            ->willReturnCallback(function (callable $callback) use ($workspace) {
                $callback('foo');

                return $workspace;
            });

        $this->getGitWrapperMock()
            ->expects(self::once())
            ->method('setPrivateKey')
            ->with('/foo/bar/private.key');

        $this->getGitWrapperMock()
            ->expects(self::once())
            ->method('cloneRepository')
            ->with('https://bar.foo', $path = 'foo/bar');

        self::assertInstanceOf(
            Hook::class,
            $hook = $hook->setOptions(
                [
                    'url' => 'https://bar.foo',
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

        self::assertInstanceOf(
            Hook::class,
            $hook->run($this->createMock(PromiseInterface::class))
        );
    }

    public function testClone()
    {
        $hook = $this->buildHook();
        $hook2 = clone $hook;

        $rp = new \ReflectionProperty(Hook::class, 'gitWrapper');
        $rp->setAccessible(true);
        self::assertNotSame($this->getGitWrapperMock(), $rp->getValue($hook2));
        self::assertSame($this->getGitWrapperMock(), $rp->getValue($hook));
    }
}
