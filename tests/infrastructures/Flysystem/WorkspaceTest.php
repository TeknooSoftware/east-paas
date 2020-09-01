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

namespace Teknoo\Tests\East\Paas\Infrastructures\Flysystem;

use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @covers \Teknoo\East\Paas\Infrastructures\Flysystem\Workspace
 * @covers \Teknoo\East\Paas\Infrastructures\Flysystem\Workspace\Generator
 * @covers \Teknoo\East\Paas\Infrastructures\Flysystem\Workspace\Running
 */
class WorkspaceTest extends TestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var JobUnitInterface
     */
    private $job;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Filesystem
     */
    public function getFilesystemMock(): Filesystem
    {
        if (!$this->filesystem instanceof Filesystem) {
            $this->filesystem = $this->createMock(Filesystem::class);
        }

        return $this->filesystem;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobUnitInterface
     */
    public function getJobMock(): JobUnitInterface
    {
        if (!$this->job instanceof JobUnitInterface) {
            $this->job = $this->createMock(JobUnitInterface::class);
        }

        return $this->job;
    }

    public function buildJobWorkspace(): Workspace
    {
        return new Workspace($this->getFilesystemMock(), '/path/root');
    }

    public function testSetJobBadJob()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->setJob(new \stdClass());
    }

    public function testSetJob()
    {
        self::assertInstanceOf(
            JobWorkspaceInterface::class,
            $this->buildJobWorkspace()->setJob($this->getJobMock())
        );
    }

    public function testWriteFileBadFile()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->writeFile(new \stdClass(), function (){});
    }

    public function testWriteFileBadCallable()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->writeFile($this->createMock(FileInterface::class), new \stdClass());
    }

    public function testWriteFileNotCallable()
    {
        $file = $this->createMock(FileInterface::class);
        $file->expects(self::any())->method('getName')->willReturn($name = 'foo');
        $file->expects(self::any())->method('getContent')->willReturn($content = 'bar');
        $file->expects(self::any())->method('getVisibility')->willReturn($v = FileInterface::VISIBILITY_PRIVATE);

        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $this->getFilesystemMock()
            ->expects(self::once())
            ->method('put')
            ->with(
                $this->callback(function ($name) {
                    return false !== \strpos($name, '/foo');
                }),
                $content,
                ['visibility' => $v]
            );

        self::assertInstanceOf(
            JobWorkspaceInterface::class,
            $this->buildJobWorkspace()->setJob($this->getJobMock())->writeFile($file)
        );
    }

    public function testWriteFileWithCallable()
    {
        $file = $this->createMock(FileInterface::class);
        $file->expects(self::any())->method('getName')->willReturn($name = 'foo');
        $file->expects(self::any())->method('getContent')->willReturn($content = 'bar');
        $file->expects(self::any())->method('getVisibility')->willReturn($v = FileInterface::VISIBILITY_PRIVATE);

        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $this->getFilesystemMock()
            ->expects(self::once())
            ->method('put')
            ->with(
                $this->callback(function ($name) {
                    return false !== \strpos($name, '/foo');
                }),
                $content,
                ['visibility' => $v]
            );

        self::assertInstanceOf(
            JobWorkspaceInterface::class,
            $this->buildJobWorkspace()->setJob($this->getJobMock())->writeFile($file, function ($name, $file) {
                self::assertNotFalse(\strpos($name, '/foo'));
                self::assertInstanceOf(FileInterface::class, $file);
            })
        );
    }

    public function testPrepareRepositoryBadRepository()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->prepareRepository(new \stdClass());
    }

    public function testPrepareRepository()
    {
        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $repository = $this->createMock(CloningAgentInterface::class);
        $repository->expects(self:: once())
            ->method('cloningIntoPath');

        $this->getFilesystemMock()
            ->expects(self::once())
            ->method('createDir')
            ->with($this->callback(function ($value) {
                return 0 === \strpos($value, '/fooBar');
            }));

        $this->buildJobWorkspace()->setJob($this->getJobMock())->prepareRepository($repository);
    }

    public function testLoadDeploymentIntoConductorBadConductor()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->loadDeploymentIntoConductor(new \stdClass());
    }

    public function testLoadDeploymentIntoConductor()
    {
        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $conductor = $this->createMock(ConductorInterface::class);
        $path = null;
        $conductor->expects(self:: once())
            ->method('prepare')
            ->with($content='foo');

        $this->getFilesystemMock()
            ->expects(self::once())
            ->method('read')
            ->with($this->callback(
                function ($value) use (&$path) {
                    $path=$value;
                    return 1 === \preg_match('#/fooBar[0-9]{7}/repository/'.Workspace::CONFIGURATION_FILE.'#iS', $value);
                }
            ))
            ->willReturn($content);

        $this->buildJobWorkspace()->setJob($this->getJobMock())->loadDeploymentIntoConductor(
            $conductor,
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testClone()
    {
        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $workspace = $this->buildJobWorkspace()->setJob($this->getJobMock());
        $workspace2 = clone $workspace;

        $rp = new \ReflectionProperty(Workspace::class, 'job');
        $rp->setAccessible(true);
        self::assertNull($rp->getValue($workspace2));
        self::assertInstanceOf(JobUnitInterface::class, $rp->getValue($workspace));
    }

    public function testHasDirectoryBadPath()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->hasDirectory(
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testHasDirectoryBadPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->hasDirectory(
            'foo',
            new \stdClass()
        );
    }

    public function testHasDirectoryGood()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $this->getFilesystemMock()
            ->expects(self::any())
            ->method('listContents')
            ->with($path = 'foo')
            ->willReturn(true);

        self::assertInstanceOf(
            Workspace::class,
            $this->buildJobWorkspace()->hasDirectory($path, $promise)
        );
    }

    public function testHasDirectoryFail()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail')->with($this->callback(fn($e) => $e instanceof \DomainException));

        $this->getFilesystemMock()
            ->expects(self::any())
            ->method('listContents')
            ->with($path = 'foo')
            ->willReturn(false);

        self::assertInstanceOf(
            Workspace::class,
            $this->buildJobWorkspace()->hasDirectory($path, $promise)
        );
    }

    public function testRunInRootWrongCallback()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->runInRoot(
            new \stdClass()
        );
    }

    public function testRunInRoot()
    {
        $called = false;
        $callback = function ($path) use (&$called) {
            self::assertIsString($path);
            $called = true;
        };

        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        self::assertInstanceOf(
            Workspace::class,
            $this->buildJobWorkspace()->setJob($this->getJobMock())->runInRoot($callback)
        );

        self::assertTrue($called);
    }
}
