<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Flysystem;

use ArrayIterator;
use DomainException;
use League\Flysystem\DirectoryListing;
use League\Flysystem\StorageAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace\Generator;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace\Running;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use TypeError;

use function preg_match;
use function strpos;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Running::class)]
#[CoversClass(Generator::class)]
#[CoversClass(Workspace::class)]
class WorkspaceTest extends TestCase
{
    private (Filesystem&MockObject)|null $filesystem = null;

    private (JobUnitInterface&MockObject)|null $job = null;

    public function getFilesystemMock(): Filesystem&MockObject
    {
        if (!$this->filesystem instanceof Filesystem) {
            $this->filesystem = $this->createMock(Filesystem::class);
        }

        return $this->filesystem;
    }

    public function getJobMock(): JobUnitInterface&MockObject
    {
        if (!$this->job instanceof JobUnitInterface) {
            $this->job = $this->createMock(JobUnitInterface::class);
        }

        return $this->job;
    }

    public function buildJobWorkspace($root = '/path/root'): Workspace
    {
        return new Workspace(
            $this->getFilesystemMock(),
            $root,
            '.paas.yaml',
        );
    }

    public function testSetJobBadJob(): void
    {
        $this->expectException(TypeError::class);
        $this->buildJobWorkspace()->setJob(new stdClass());
    }

    public function testSetJob(): void
    {
        $this->assertInstanceOf(JobWorkspaceInterface::class, $this->buildJobWorkspace()->setJob($this->getJobMock()));
    }

    public function testWriteFileBadFile(): void
    {
        $this->expectException(TypeError::class);
        $this->buildJobWorkspace()->writeFile(new stdClass(), function (): void {});
    }

    public function testWriteFileBadCallable(): void
    {
        $this->expectException(TypeError::class);
        $this->buildJobWorkspace()->writeFile($this->createMock(FileInterface::class), new stdClass());
    }

    public function testWriteFileWithAGenerator(): void
    {
        $this->expectException(RuntimeException::class);

        $file = $this->createMock(FileInterface::class);
        $file->method('getName')->willReturn($name = 'foo');
        $file->method('getContent')->willReturn($content = 'bar');
        $file->method('getVisibility')->willReturn($v = Visibility::Private);

        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $this->assertInstanceOf(JobWorkspaceInterface::class, $this->buildJobWorkspace()->writeFile($file, function ($name, $file): void {
            $this->assertStringContainsString('/foo', (string) $name);
            $this->assertInstanceOf(FileInterface::class, $file);
        }));
    }

    public function testWriteFileNotCallable(): void
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getName')->willReturn($name = 'foo');
        $file->method('getContent')->willReturn($content = 'bar');
        $file->method('getVisibility')->willReturn($v = Visibility::Private);

        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $this->getFilesystemMock()
            ->expects($this->once())
            ->method('write')
            ->with(
                $this->callback(fn ($name): bool => str_contains((string) $name, '/foo')),
                $content,
                ['visibility' => $v->value]
            );

        $this->assertInstanceOf(JobWorkspaceInterface::class, $this->buildJobWorkspace()->setJob($this->getJobMock())->writeFile($file));
    }

    public function testWriteFileWithCallable(): void
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getName')->willReturn($name = 'foo');
        $file->method('getContent')->willReturn($content = 'bar');
        $file->method('getVisibility')->willReturn($v = Visibility::Private);

        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $this->getFilesystemMock()
            ->expects($this->once())
            ->method('write')
            ->with(
                $this->callback(fn ($name): bool => str_contains((string) $name, '/foo')),
                $content,
                ['visibility' => $v->value]
            );

        $this->assertInstanceOf(JobWorkspaceInterface::class, $this->buildJobWorkspace()->setJob($this->getJobMock())->writeFile($file, function ($path, $filename, $file): void {
            $this->assertStringStartsWith('/path/root/fooBar', $path);
            $this->assertEquals('foo', $filename);
            $this->assertInstanceOf(FileInterface::class, $file);
        }));
    }

    public function testWriteFileWithCallableWithRootWithSlash(): void
    {
        $file = $this->createMock(FileInterface::class);
        $file->method('getName')->willReturn($name = 'foo');
        $file->method('getContent')->willReturn($content = 'bar');
        $file->method('getVisibility')->willReturn($v = Visibility::Private);

        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $this->getFilesystemMock()
            ->expects($this->once())
            ->method('write')
            ->with(
                $this->callback(fn ($name): bool => str_contains((string) $name, '/foo')),
                $content,
                ['visibility' => $v->value]
            );

        $this->assertInstanceOf(JobWorkspaceInterface::class, $this->buildJobWorkspace('/path/root/')->setJob($this->getJobMock())->writeFile($file, function ($path, $filename, $file): void {
            $this->assertStringStartsWith('/path/root/fooBar', $path);
            $this->assertEquals('foo', $filename);
            $this->assertInstanceOf(FileInterface::class, $file);
        }));
    }

    public function testPrepareRepositoryBadRepository(): void
    {
        $this->expectException(TypeError::class);
        $this->buildJobWorkspace()->prepareRepository(new stdClass());
    }

    public function testPrepareRepositoryWithGenerator(): void
    {
        $this->expectException(RuntimeException::class);

        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $repository = $this->createMock(CloningAgentInterface::class);
        $repository->expects($this-> never())
            ->method('cloningIntoPath');

        $this->buildJobWorkspace()->prepareRepository($repository);
    }

    public function testPrepareRepository(): void
    {
        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $repository = $this->createMock(CloningAgentInterface::class);
        $repository->expects($this-> once())
            ->method('cloningIntoPath');

        $this->getFilesystemMock()
            ->expects($this->once())
            ->method('createDirectory')
            ->with($this->callback(fn ($value): bool => str_starts_with((string) $value, '/fooBar')));

        $this->buildJobWorkspace()->setJob($this->getJobMock())->prepareRepository($repository);
    }

    public function testLoadDeploymentIntoConductorBadConductor(): void
    {
        $this->expectException(TypeError::class);
        $this->buildJobWorkspace()->loadDeploymentIntoConductor(new stdClass());
    }

    public function testLoadDeploymentIntoConductorWithGenerator(): void
    {
        $this->expectException(RuntimeException::class);

        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $conductor = $this->createMock(ConductorInterface::class);

        $this->buildJobWorkspace()->loadDeploymentIntoConductor(
            $conductor,
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testLoadDeploymentIntoConductor(): void
    {
        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $conductor = $this->createMock(ConductorInterface::class);
        $path = null;
        $conductor->expects($this-> once())
            ->method('prepare')
            ->with($content = 'foo');

        $this->getFilesystemMock()
            ->expects($this->once())
            ->method('read')
            ->with($this->callback(
                function ($value) use (&$path): bool {
                    $path = $value;
                    return 1 === preg_match('#/fooBar\d{7}/repository/\.paas\.yaml#iS', $value);
                }
            ))
            ->willReturn($content);

        $this->buildJobWorkspace()->setJob($this->getJobMock())->loadDeploymentIntoConductor(
            $conductor,
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testClone(): void
    {
        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $workspace = $this->buildJobWorkspace()->setJob($this->getJobMock());
        $workspace2 = clone $workspace;

        $rp = new ReflectionProperty(Workspace::class, 'job');
        $this->assertNull($rp->getValue($workspace2));
        $this->assertInstanceOf(JobUnitInterface::class, $rp->getValue($workspace));
    }

    public function testHasDirectoryBadPath(): void
    {
        $this->expectException(TypeError::class);
        $this->buildJobWorkspace()->hasDirectory(
            new stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testHasDirectoryBadPromise(): void
    {
        $this->expectException(TypeError::class);
        $this->buildJobWorkspace()->hasDirectory(
            'foo',
            new stdClass()
        );
    }

    public function testHasDirectoryGood(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->getFilesystemMock()
            ->method('listContents')
            ->with($path = 'foo')
            ->willReturn(new DirectoryListing(new ArrayIterator([$this->createMock(StorageAttributes::class)])));

        $this->assertInstanceOf(Workspace::class, $this->buildJobWorkspace()->hasDirectory($path, $promise));
    }

    public function testHasDirectoryFail(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail')->with($this->callback(fn ($e): bool => $e instanceof DomainException));

        $this->getFilesystemMock()
            ->method('listContents')
            ->with($path = 'foo')
            ->willReturn(new DirectoryListing(new ArrayIterator([])));

        $this->assertInstanceOf(Workspace::class, $this->buildJobWorkspace()->hasDirectory($path, $promise));
    }

    public function testRunInRepositoryPathWrongCallback(): void
    {
        $this->expectException(TypeError::class);
        $this->buildJobWorkspace()->runInRepositoryPath(
            new stdClass()
        );
    }

    public function testRunInRepositoryPath(): void
    {
        $called = false;
        $callback = function ($path) use (&$called): void {
            $this->assertIsString($path);
            $called = true;
        };

        $this->getJobMock()
            ->method('getId')
            ->willReturn('fooBar');

        $this->assertInstanceOf(Workspace::class, $this->buildJobWorkspace()->setJob($this->getJobMock())->runInRepositoryPath($callback));

        $this->assertTrue($called);
    }

    public function testCleanWithGenerator(): void
    {
        $this->assertInstanceOf(Workspace::class, $this->buildJobWorkspace()->clean());
    }

    public function testClean(): void
    {
        $this->getFilesystemMock()
            ->method('fileExists')
            ->willReturn(true);

        $this->getFilesystemMock()
            ->expects($this->once())
            ->method('deleteDirectory');

        $this->assertInstanceOf(Workspace::class, $this->buildJobWorkspace()->setJob($this->getJobMock())->clean());
    }

    public function testCleanException(): void
    {
        $this->getFilesystemMock()
            ->method('fileExists')
            ->willReturn(true);

        $this->getFilesystemMock()
            ->expects($this->once())
            ->method('deleteDirectory')
            ->willThrowException(new RuntimeException('test'));

        $object = $this->buildJobWorkspace()->setJob($this->getJobMock());
        unset($object);
    }
}
