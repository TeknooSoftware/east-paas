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

namespace Teknoo\Tests\East\Paas\Infrastructures\Flysystem;

use League\Flysystem\DirectoryListing;
use League\Flysystem\StorageAttributes;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use function strpos;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
        return new Workspace(
            $this->getFilesystemMock(),
            '/path/root',
            '.paas.yaml',
        );
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

    public function testWriteFileWithAGenerator()
    {
        $this->expectException(\RuntimeException::class);

        $file = $this->createMock(FileInterface::class);
        $file->expects(self::any())->method('getName')->willReturn($name = 'foo');
        $file->expects(self::any())->method('getContent')->willReturn($content = 'bar');
        $file->expects(self::any())->method('getVisibility')->willReturn($v = Visibility::Private);

        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        self::assertInstanceOf(
            JobWorkspaceInterface::class,
            $this->buildJobWorkspace()->writeFile($file, function ($name, $file) {
                self::assertNotFalse(strpos((string) $name, '/foo'));
                self::assertInstanceOf(FileInterface::class, $file);
            })
        );
    }

    public function testWriteFileNotCallable()
    {
        $file = $this->createMock(FileInterface::class);
        $file->expects(self::any())->method('getName')->willReturn($name = 'foo');
        $file->expects(self::any())->method('getContent')->willReturn($content = 'bar');
        $file->expects(self::any())->method('getVisibility')->willReturn($v = Visibility::Private);

        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $this->getFilesystemMock()
            ->expects(self::once())
            ->method('write')
            ->with(
                $this->callback(fn($name) => str_contains((string) $name, '/foo')),
                $content,
                ['visibility' => $v->value]
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
        $file->expects(self::any())->method('getVisibility')->willReturn($v = Visibility::Private);

        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $this->getFilesystemMock()
            ->expects(self::once())
            ->method('write')
            ->with(
                $this->callback(fn($name) => str_contains((string) $name, '/foo')),
                $content,
                ['visibility' => $v->value]
            );

        self::assertInstanceOf(
            JobWorkspaceInterface::class,
            $this->buildJobWorkspace()->setJob($this->getJobMock())->writeFile($file, function ($path, $filename, $file) {
                self::assertEquals($path, '/path/root');
                self::assertNotFalse(strpos($filename, '/foo'));
                self::assertInstanceOf(FileInterface::class, $file);
            })
        );
    }

    public function testPrepareRepositoryBadRepository()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->prepareRepository(new \stdClass());
    }

    public function testPrepareRepositoryWithGenerator()
    {
        $this->expectException(\RuntimeException::class);

        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $repository = $this->createMock(CloningAgentInterface::class);
        $repository->expects(self:: never())
            ->method('cloningIntoPath');

        $this->buildJobWorkspace()->prepareRepository($repository);
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
            ->method('createDirectory')
            ->with($this->callback(fn($value) => str_starts_with($value, '/fooBar')));

        $this->buildJobWorkspace()->setJob($this->getJobMock())->prepareRepository($repository);
    }

    public function testLoadDeploymentIntoConductorBadConductor()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->loadDeploymentIntoConductor(new \stdClass());
    }

    public function testLoadDeploymentIntoConductorWithGenerator()
    {
        $this->expectException(\RuntimeException::class);

        $this->getJobMock()
            ->expects(self::any())
            ->method('getId')
            ->willReturn('fooBar');

        $conductor = $this->createMock(ConductorInterface::class);

        $this->buildJobWorkspace()->loadDeploymentIntoConductor(
            $conductor,
            $this->createMock(PromiseInterface::class)
        );
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
                    return 1 === \preg_match('#/fooBar\d{7}/repository/\.paas\.yaml#iS', $value);
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
            ->willReturn(new DirectoryListing(new \ArrayIterator([$this->createMock(StorageAttributes::class)])));

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
            ->willReturn(new DirectoryListing(new \ArrayIterator([])));

        self::assertInstanceOf(
            Workspace::class,
            $this->buildJobWorkspace()->hasDirectory($path, $promise)
        );
    }

    public function testRunInRepositoryPathWrongCallback()
    {
        $this->expectException(\TypeError::class);
        $this->buildJobWorkspace()->runInRepositoryPath(
            new \stdClass()
        );
    }

    public function testRunInRepositoryPath()
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
            $this->buildJobWorkspace()->setJob($this->getJobMock())->runInRepositoryPath($callback)
        );

        self::assertTrue($called);
    }

    public function testCleanWithGenerator()
    {
        self::assertInstanceOf(
            Workspace::class,
            $this->buildJobWorkspace()->clean()
        );
    }

    public function testClean()
    {
        $this->getFilesystemMock()
            ->expects(self::any())
            ->method('fileExists')
            ->willReturn(true);

        $this->getFilesystemMock()
            ->expects(self::once())
            ->method('deleteDirectory');

        self::assertInstanceOf(
            Workspace::class,
            $this->buildJobWorkspace()->setJob($this->getJobMock())->clean()
        );
    }

    public function testCleanException()
    {
        $this->getFilesystemMock()
            ->expects(self::any())
            ->method('fileExists')
            ->willReturn(true);

        $this->getFilesystemMock()
            ->expects(self::once())
            ->method('deleteDirectory')
            ->willThrowException(new \RuntimeException('test'));

        $object = $this->buildJobWorkspace()->setJob($this->getJobMock());
        unset($object);
    }
}
