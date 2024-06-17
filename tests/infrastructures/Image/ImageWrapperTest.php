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

namespace Teknoo\Tests\East\Paas\Infrastructures\Image;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use stdClass;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Infrastructures\Image\ImageWrapper;
use Teknoo\East\Paas\Infrastructures\Image\Contracts\ProcessFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\Image\ImageWrapper\Generator;
use Teknoo\East\Paas\Infrastructures\Image\ImageWrapper\Running;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Object\XRegistryAuth;
use TypeError;

use function set_time_limit;
use function strpos;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Running::class)]
#[CoversClass(Generator::class)]
#[CoversClass(ImageWrapper::class)]
class ImageWrapperTest extends TestCase
{
    private ?ProcessFactoryInterface $processFactory = null;

    protected function tearDown(): void
    {
        set_time_limit(0);
    }

    /**
     * @return ProcessFactoryInterface|MockObject
     */
    public function getProcessFactoryMock(): ?ProcessFactoryInterface
    {
        if (!$this->processFactory instanceof ProcessFactoryInterface) {
            $this->processFactory = $this->createMock(ProcessFactoryInterface::class);
        }

        return $this->processFactory;
    }

    public function buildWrapper($timeout = 300): ImageWrapper
    {
        return new ImageWrapper(
            'buildah',
            [
                'image' => 'foo',
                'embedded-volume-image' => 'foo',
                'volume' => 'bar',
            ],
            $this->getProcessFactoryMock(),
            'foo',
            $timeout
        );
    }

    public function testConstructorWithoutImageTemplate()
    {
        $this->expectException(DomainException::class);
        new ImageWrapper(
            'buildah',
            [
                'volume' => 'bar',
            ],
            $this->getProcessFactoryMock(),
            'foo',
            0,
        );
    }

    public function testConstructorWithoutVolumeTemplate()
    {
        $this->expectException(DomainException::class);
        new ImageWrapper(
            'buildah',
            [
                'image' => 'foo',
            ],
            $this->getProcessFactoryMock(),
            'foo',
            0
        );
    }

    public function testConfigureWrongProjectId()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->configure(
            'bar',
            new stdClass(),
            $this->createMock(IdentityInterface::class)
        );
    }

    public function testConfigureWrongUrl()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->configure(
            new stdClass(),
            'bar',
            $this->createMock(IdentityInterface::class)
        );
    }

    public function testConfigureWrongAuth()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->configure(
            'foo',
            'bar',
            new stdClass()
        );
    }

    public function testConfigureNotSupported()
    {
        $this->expectException(RuntimeException::class);
        $this->buildWrapper()->configure(
            'foo',
            'bar',
            $this->createMock(IdentityInterface::class)
        );
    }

    public function testConfigure()
    {
        self::assertInstanceOf(
            ImageWrapper::class,
            $this->buildWrapper()->configure(
                'foo',
                'bar',
                $this->createMock(XRegistryAuth::class)
            )
        );
    }
    
    public function testBuildImagesWrongCompileDeployment()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->buildImages(
            new stdClass(),
            'foo',
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testBuildImagesWrongWorkingPath()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->buildImages(
            $this->createMock(CompiledDeploymentInterface::class),
            new stdClass(),
            $this->createMock(PromiseInterface::class),
        );
    }
    
    public function testBuildImagesWrongPromise()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->buildImages(
            $this->createMock(CompiledDeploymentInterface::class),
            'foo',
            new stdClass()
        );
    }
    
    public function testBuildImagesWithError()
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachBuildable')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image2 = new Image('bar', '/bar', true, '7.4', []);

                $callback($image1);
                $callback($image2);
                return $cd;
            });

        $cd->expects($this->exactly(2))
            ->method('updateBuildable')
            ->willReturnCallback(function (BuildableInterface $oldImage, BuildableInterface $image) use ($cd) {
                self::assertEquals(0, strpos((string) $image->getUrl(), 'repository.teknoo.run'));

                return $cd;
            });

        $p1 = $this->createMock(Process::class);
        $p1->expects($this->once())->method('isSuccessful')->willReturn(false);
        $p1->expects($this->once())->method('getErrorOutput')->willReturn('foo');

        $p2 = $this->createMock(Process::class);
        $p2->expects($this->any())->method('isSuccessful')->willReturn(true);
        $p2->expects($this->once())->method('isRunning')->willReturn(true);
        $p2->expects($this->once())->method('stop');
        $p2->expects($this->never())->method('getOutput');

        $this->getProcessFactoryMock()
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls($p1, $p2);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $builder = $this->buildWrapper();

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder = $builder->configure(
                'bar',
                'repository.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder->buildImages(
                $cd,
                'foo',
                $promise
            )
        );
    }

    public function testBuildImages()
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachBuildable')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $volumes = [
                    new PersistentVolume('v!', '/bar', 'pv'),
                    new Volume('v!', ['/bar'], '/volume', '/mount', ['/var*'], true),
                ];
                $image2 = new EmbeddedVolumeImage('bar1', 'bar', 'bar', 'ori-bar', $volumes);

                $callback($image1);
                $callback($image2);
                return $cd;
            });

        $cd->expects($this->exactly(2))
            ->method('updateBuildable')
            ->willReturnCallback(function (BuildableInterface $oldImage, BuildableInterface $image) use ($cd) {
                self::assertEquals(0, strpos((string) $image->getUrl(), 'repository.teknoo.run'));

                return $cd;
            });

        $this->getProcessFactoryMock()
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                $process = $this->createMock(Process::class);
                $process->expects($this->once())->method('isSuccessful')->willReturn(true);
                $process->expects($this->once())->method('getOutput')->willReturn('foo');

                return $process;
            });

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success')->with('foo');
        $promise->expects($this->never())->method('fail');

        $builder = $this->buildWrapper();

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder = $builder->configure(
                'bar',
                'repository.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder->buildImages(
                $cd,
                'foo',
                $promise
            )
        );
    }

    public function testBuildImagesWithoutTimeout()
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachBuildable')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image2 = new Image('bar', '/bar', true, '7.4', []);

                $callback($image1);
                $callback($image2);
                return $cd;
            });

        $cd->expects($this->exactly(2))
            ->method('updateBuildable')
            ->willReturnCallback(function (BuildableInterface $oldImage, BuildableInterface $image) use ($cd) {
                self::assertEquals(0, strpos((string) $image->getUrl(), 'repository.teknoo.run'));

                return $cd;
            });

        $this->getProcessFactoryMock()
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                $process = $this->createMock(Process::class);
                $process->expects($this->once())->method('isSuccessful')->willReturn(true);
                $process->expects($this->once())->method('getOutput')->willReturn('foo');

                return $process;
            });

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success')->with('foo');
        $promise->expects($this->never())->method('fail');

        $builder = $this->buildWrapper(0);

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder = $builder->configure(
                'bar',
                'repository.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder->buildImages(
                $cd,
                'foo',
                $promise
            )
        );
    }
    
    public function testBuildVolumesWrongCompileDeployment()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->buildVolumes(
            new stdClass(),
            'foo',
            $this->createMock(PromiseInterface::class)
        );
    }
    
    public function testBuildVolumesWrongWorkingPath()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->buildVolumes(
            $this->createMock(CompiledDeploymentInterface::class),
            new stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testBuildVolumesWrongPromise()
    {
        $this->expectException(TypeError::class);
        $this->buildWrapper()->buildVolumes(
            $this->createMock(CompiledDeploymentInterface::class),
            'foo',
            new stdClass()
        );
    }

    public function testBuildVolumesWithError()
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo/bar'], '/bar', '/mount');

                $callback('foo', $volume1);
                $callback('bar', $volume2);
                return $cd;
            });

        $cd->expects($this->exactly(2))
            ->method('addVolume')
            ->with(
                $this->callback(
                    fn ($value) => match ($value) {
                        'foo' => true,
                        'bar' => true,
                        default => false,
                    }
                )
            )
            ->willReturnCallback(function ($name, Volume $volume) use ($cd) {
                self::assertEquals(0, strpos((string) $volume->getUrl(), 'repository.teknoo.run'));

                return $cd;
            });

        $p1 = $this->createMock(Process::class);
        $p1->expects($this->once())->method('isSuccessful')->willReturn(false);
        $p1->expects($this->once())->method('getErrorOutput')->willReturn('foo');

        $p2 = $this->createMock(Process::class);
        $p2->expects($this->any())->method('isSuccessful')->willReturn(true);
        $p2->expects($this->once())->method('isRunning')->willReturn(true);
        $p2->expects($this->once())->method('stop');
        $p2->expects($this->never())->method('getOutput');

        $this->getProcessFactoryMock()
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls($p1, $p2);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $builder = $this->buildWrapper();

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder = $builder->configure(
                'bar',
                'repository.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder->buildVolumes(
                $cd,
                'foo',
                $promise
            )
        );
    }

    public function testBuildVolumes()
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $volume1 = new Volume('foo1', ['foo' => 'bar'], '/foo', '/mount');
                $volume2 = new Volume('bar1', ['bar' => 'foo'], '/bar', '/mount');
                $volume3 = $this->createMock(PersistentVolume::class);

                $callback('foo', $volume1);
                $callback('bar', $volume2);
                $callback('bar', $volume3);
                return $cd;
            });

        $cd->expects($this->exactly(2))
            ->method('addVolume')
            ->with(
                $this->callback(
                    fn ($value) => match ($value) {
                        'foo' => true,
                        'bar' => true,
                        default => false,
                    }
                )
            )
            ->willReturnCallback(function ($name, Volume $volume) use ($cd) {
                self::assertEquals(0, strpos((string) $volume->getUrl(), 'repository.teknoo.run'));

                return $cd;
            });

        $this->getProcessFactoryMock()
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                $process = $this->createMock(Process::class);
                $process->expects($this->once())->method('isSuccessful')->willReturn(true);
                $process->expects($this->once())->method('getOutput')->willReturn('foo');

                return $process;
            });

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success')->with('foo');
        $promise->expects($this->never())->method('fail');

        $builder = $this->buildWrapper();

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder = $builder->configure(
                'bar',
                'repository.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            ImageWrapper::class,
            $builder->buildVolumes(
                $cd,
                'foo',
                $promise
            )
        );
    }
}
