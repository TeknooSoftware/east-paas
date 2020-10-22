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

namespace Teknoo\Tests\East\Paas\Infrastructures\Docker;

use Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper;
use Teknoo\East\Paas\Infrastructures\Docker\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Docker\Contracts\ScriptWriterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Container\Volume;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Object\XRegistryAuth;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper
 * @covers \Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper\Generator
 * @covers \Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper\Running
 */
class BuilderWrapperTest extends TestCase
{
    private ?ProcessFactoryInterface $processFactory = null;

    private ?ScriptWriterInterface $scriptWriter = null;

    protected function tearDown(): void
    {
        parent::tearDown();
        \set_time_limit(0);
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

    /**
     * @return ScriptWriterInterface|MockObject
     */
    public function getScriptWriterMock(): ?ScriptWriterInterface
    {
        if (!$this->scriptWriter instanceof ScriptWriterInterface) {
            $this->scriptWriter = $this->createMock(ScriptWriterInterface::class);
        }

        return $this->scriptWriter;
    }

    public function buildWrapper($timeout = 300): BuilderWrapper
    {
        return new BuilderWrapper(
            'docker',
            [
                'image' => 'foo',
                'volume' => 'bar',
            ],
            $this->getProcessFactoryMock(),
            $timeout,
            $this->getScriptWriterMock(),
            '_mount'
        );
    }

    public function testConstructorWithoutImageTemplate()
    {
        $this->expectException(\DomainException::class);
        new BuilderWrapper(
            'docker',
            [
                'volume' => 'bar',
            ],
            $this->getProcessFactoryMock(),
            0,
            $this->getScriptWriterMock(),
            '_mount'
        );
    }

    public function testConstructorWithoutVolumeTemplate()
    {
        $this->expectException(\DomainException::class);
        new BuilderWrapper(
            'docker',
            [
                'image' => 'foo',
            ],
            $this->getProcessFactoryMock(),
            0,
            $this->getScriptWriterMock(),
            '_mount'
        );
    }

    public function testConfigureWrongProjectId()
    {
        $this->expectException(\TypeError::class);
        $this->buildWrapper()->configure(
            'bar',
            new \stdClass(),
            $this->createMock(IdentityInterface::class)
        );
    }

    public function testConfigureWrongUrl()
    {
        $this->expectException(\TypeError::class);
        $this->buildWrapper()->configure(
            new \stdClass(),
            'bar',
            $this->createMock(IdentityInterface::class)
        );
    }

    public function testConfigureWrongAuth()
    {
        $this->expectException(\TypeError::class);
        $this->buildWrapper()->configure(
            'foo',
            'bar',
            new \stdClass()
        );
    }

    public function testConfigureNotSupported()
    {
        $this->expectException(\RuntimeException::class);
        $this->buildWrapper()->configure(
            'foo',
            'bar',
            $this->createMock(IdentityInterface::class)
        );
    }

    public function testConfigure()
    {
        self::assertInstanceOf(
            BuilderWrapper::class,
            $this->buildWrapper()->configure(
                'foo',
                'bar',
                $this->createMock(XRegistryAuth::class)
            )
        );
    }
    
    public function testBuildImagesWrongCompileDeployment()
    {
        $this->expectException(\TypeError::class);
        $this->buildWrapper()->buildImages(
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }
    
    public function testBuildImagesWrongPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildWrapper()->buildImages(
            $this->createMock(CompiledDeployment::class),
            new \stdClass()
        );
    }
    
    public function testBuildImagesWithError()
    {
        $cd = $this->createMock(CompiledDeployment::class);
        $cd->expects(self::once())
            ->method('foreachImage')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image2 = new Image('bar', '/bar', true, '7.4', []);

                $callback($image1);
                $callback($image2);
                return $cd;
            });

        $cd->expects(self::exactly(2))
            ->method('updateImage')
            ->willReturnCallback(function (Image $oldImage, Image $image) use ($cd) {
                self::assertEquals(0, \strpos($image->getUrl(), 'docker.teknoo.run'));

                return $cd;
            });

        $this->getScriptWriterMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                return \tempnam('/tmp', 'east-paas-docker-') . '.sh';
            });

        $p1 = $this->createMock(Process::class);
        $p1->expects(self::once())->method('isSuccessful')->willReturn(false);
        $p1->expects(self::once())->method('getErrorOutput')->willReturn('foo');

        $p2 = $this->createMock(Process::class);
        $p2->expects(self::any())->method('isSuccessful')->willReturn(true);
        $p2->expects(self::once())->method('isRunning')->willReturn(true);
        $p2->expects(self::once())->method('stop');
        $p2->expects(self::never())->method('getOutput');

        $this->getProcessFactoryMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls($p1, $p2);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $builder = $this->buildWrapper();

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder = $builder->configure(
                'bar',
                'docker.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder->buildImages(
                $cd,
                $promise
            )
        );
    }

    public function testBuildImages()
    {
        $cd = $this->createMock(CompiledDeployment::class);
        $cd->expects(self::once())
            ->method('foreachImage')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image2 = new Image('bar', '/bar', true, '7.4', []);

                $callback($image1);
                $callback($image2);
                return $cd;
            });

        $cd->expects(self::exactly(2))
            ->method('updateImage')
            ->willReturnCallback(function (Image $oldImage, Image $image) use ($cd) {
                self::assertEquals(0, \strpos($image->getUrl(), 'docker.teknoo.run'));

                return $cd;
            });

        $this->getScriptWriterMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                return \tempnam('/tmp', 'east-paas-docker-') . '.sh';
            });

        $this->getProcessFactoryMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                $process = $this->createMock(Process::class);
                $process->expects(self::once())->method('isSuccessful')->willReturn(true);
                $process->expects(self::once())->method('getOutput')->willReturn('foo');

                return $process;
            });

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with('foo');
        $promise->expects(self::never())->method('fail');

        $builder = $this->buildWrapper();

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder = $builder->configure(
                'bar',
                'docker.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder->buildImages(
                $cd,
                $promise
            )
        );
    }

    public function testBuildImagesWithoutTimeout()
    {
        $cd = $this->createMock(CompiledDeployment::class);
        $cd->expects(self::once())
            ->method('foreachImage')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $image1 = new Image('foo', '/foo', true, '7.4', ['foo' => 'bar']);
                $image2 = new Image('bar', '/bar', true, '7.4', []);

                $callback($image1);
                $callback($image2);
                return $cd;
            });

        $cd->expects(self::exactly(2))
            ->method('updateImage')
            ->willReturnCallback(function (Image $oldImage, Image $image) use ($cd) {
                self::assertEquals(0, \strpos($image->getUrl(), 'docker.teknoo.run'));

                return $cd;
            });

        $this->getScriptWriterMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                return \tempnam('/tmp', 'east-paas-docker-') . '.sh';
            });

        $this->getProcessFactoryMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                $process = $this->createMock(Process::class);
                $process->expects(self::once())->method('isSuccessful')->willReturn(true);
                $process->expects(self::once())->method('getOutput')->willReturn('foo');

                return $process;
            });

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with('foo');
        $promise->expects(self::never())->method('fail');

        $builder = $this->buildWrapper(0);

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder = $builder->configure(
                'bar',
                'docker.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder->buildImages(
                $cd,
                $promise
            )
        );
    }
    
    public function testBuildVolumesWrongCompileDeployment()
    {
        $this->expectException(\TypeError::class);
        $this->buildWrapper()->buildVolumes(
            new \stdClass(),
            'foo',
            $this->createMock(PromiseInterface::class)
        );
    }
    
    public function testBuildVolumesWrongWorkingPath()
    {
        $this->expectException(\TypeError::class);
        $this->buildWrapper()->buildVolumes(
            $this->createMock(CompiledDeployment::class),
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testBuildVolumesWrongPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildWrapper()->buildVolumes(
            $this->createMock(CompiledDeployment::class),
            'foo',
            new \stdClass()
        );
    }

    public function testBuildVolumesWithError()
    {
        $cd = $this->createMock(CompiledDeployment::class);
        $cd->expects(self::once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $volume1 = new Volume('foo1', '/foo', ['foo' => 'bar']);
                $volume2 = new Volume('bar1', '/bar', ['bar' => 'foo']);

                $callback('foo', $volume1);
                $callback('bar', $volume2);
                return $cd;
            });

        $cd->expects(self::exactly(2))
            ->method('defineVolume')
            ->withConsecutive(
                ['foo'],
                ['bar']
            )
            ->willReturnCallback(function ($name, Volume $volume) use ($cd) {
                self::assertEquals(0, \strpos($volume->getUrl(), 'docker.teknoo.run'));

                return $cd;
            });

        $this->getScriptWriterMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                return \tempnam('/tmp', 'east-paas-docker-') . '.sh';
            });

        $p1 = $this->createMock(Process::class);
        $p1->expects(self::once())->method('isSuccessful')->willReturn(false);
        $p1->expects(self::once())->method('getErrorOutput')->willReturn('foo');

        $p2 = $this->createMock(Process::class);
        $p2->expects(self::any())->method('isSuccessful')->willReturn(true);
        $p2->expects(self::once())->method('isRunning')->willReturn(true);
        $p2->expects(self::once())->method('stop');
        $p2->expects(self::never())->method('getOutput');

        $this->getProcessFactoryMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls($p1, $p2);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $builder = $this->buildWrapper();

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder = $builder->configure(
                'bar',
                'docker.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder->buildVolumes(
                $cd,
                'foo',
                $promise
            )
        );
    }

    public function testBuildVolumes()
    {
        $cd = $this->createMock(CompiledDeployment::class);
        $cd->expects(self::once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd) {
                $volume1 = new Volume('foo1', '/foo', ['foo' => 'bar']);
                $volume2 = new Volume('bar1', '/bar', ['bar' => 'foo']);

                $callback('foo', $volume1);
                $callback('bar', $volume2);
                return $cd;
            });

        $cd->expects(self::exactly(2))
            ->method('defineVolume')
            ->withConsecutive(
                ['foo'],
                ['bar']
            )
            ->willReturnCallback(function ($name, Volume $volume) use ($cd) {
                self::assertEquals(0, \strpos($volume->getUrl(), 'docker.teknoo.run'));

                return $cd;
            });

        $this->getScriptWriterMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                return \tempnam('/tmp', 'east-paas-docker-') . '.sh';
            });

        $this->getProcessFactoryMock()
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function () {
                $process = $this->createMock(Process::class);
                $process->expects(self::once())->method('isSuccessful')->willReturn(true);
                $process->expects(self::once())->method('getOutput')->willReturn('foo');

                return $process;
            });

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::exactly(2))->method('success')->with('foo');
        $promise->expects(self::never())->method('fail');

        $builder = $this->buildWrapper();

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder = $builder->configure(
                'bar',
                'docker.teknoo.run',
                new XRegistryAuth('foo', 'bar', '', '', '')
            )
        );

        self::assertInstanceOf(
            BuilderWrapper::class,
            $builder->buildVolumes(
                $cd,
                'foo',
                $promise
            )
        );
    }
}
