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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose\Transcriber;

use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use stdClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\ConfigMapTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ConfigMapTranscriber::class)]
class ConfigMapTranscriberTest extends TestCase
{
    private function buildTranscriber(): ConfigMapTranscriber
    {
        return new ConfigMapTranscriber();
    }

    public function testTranscribe(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Map('app', ['DEBUG' => 'true', 'TZ' => 'UTC']), 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with([
            'configs' => [
                'prj-app-map-DEBUG' => ['file' => './configs/prj-app-map/DEBUG'],
                'prj-app-map-TZ' => ['file' => './configs/prj-app-map/TZ'],
            ],
        ]);
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            ConfigMapTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                accumulator: $generation,
                promise: $promise,
                defaultsBag: $this->createStub(DefaultsBag::class),
                namespace: 'default',
            ),
        );

        //One Compose config per key, each backed by its own file (mountable like a Kubernetes ConfigMap)
        self::assertSame(
            [
                'configs' => [
                    'prj-app-map-DEBUG' => ['file' => './configs/prj-app-map/DEBUG'],
                    'prj-app-map-TZ' => ['file' => './configs/prj-app-map/TZ'],
                ],
            ],
            $generation->getComposeFile(),
        );

        self::assertSame(
            [
                'configs/prj-app-map/DEBUG' => 'true',
                'configs/prj-app-map/TZ' => 'UTC',
            ],
            $generation->getFiles(),
        );
    }

    public function testTranscribeSanitizesKeysAndJoinsArrayValues(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Map('single', ['app/conf.ini' => ['a=1', 'b=2'], 'flag' => true]), '');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame(
            [
                'configs' => [
                    'single-map-app_conf.ini' => ['file' => './configs/single-map/app_conf.ini'],
                    'single-map-flag' => ['file' => './configs/single-map/flag'],
                ],
            ],
            $generation->getComposeFile(),
        );

        $files = $generation->getFiles();
        self::assertSame("a=1\nb=2", $files['configs/single-map/app_conf.ini']);
        self::assertSame('1', $files['configs/single-map/flag']);
    }

    public function testTranscribeKeepsMapsSeparated(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Map('first', ['a' => '1', 'b' => '2']), 'prj');
                $callback(new Map('second', ['a' => '10']), 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame(
            [
                'configs/prj-first-map/a' => '1',
                'configs/prj-first-map/b' => '2',
                'configs/prj-second-map/a' => '10',
            ],
            $generation->getFiles(),
        );
    }


    public function testTranscribeFailure(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Map('app', ['DEBUG' => 'true']), 'prj');

                return $cd;
            });

        $accumulator = $this->createMock(AccumulatorInterface::class);
        $accumulator->expects($this->once())
            ->method('addConfig')
            ->willThrowException(new RuntimeException('boom'));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail')->with($this->isInstanceOf(RuntimeException::class));

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $accumulator,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }


    public function testTranscribeWritesAnEmptyFileForANonScalarValue(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Map('app', ['empty' => null, 'object' => new stdClass()]), '');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame(
            ['configs/app-map/empty' => '', 'configs/app-map/object' => ''],
            $generation->getFiles(),
        );
    }
}
