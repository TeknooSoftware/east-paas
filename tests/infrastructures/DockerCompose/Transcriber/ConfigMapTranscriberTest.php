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
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
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
        $promise->expects($this->once())->method('success');
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

        self::assertSame(
            [
                'configs' => [
                    'prj-app-map' => ['file' => './configs/prj-app-map'],
                ],
            ],
            $generation->getComposeFile(),
        );

        $files = $generation->getFiles();
        self::assertSame("DEBUG=true\nTZ=UTC", $files['configs/prj-app-map']);
        self::assertArrayNotHasKey('configs/prj-app-map__DEBUG', $files);
        self::assertArrayNotHasKey('configs/prj-app-map__TZ', $files);
    }

    public function testTranscribeKeepsOneConfigPerMapWithoutFusing(): void
    {
        //Two maps, the first with 3 keys and the second with 2 keys: the result must be exactly two
        //configs (one per map), each carrying its own keys — never 5 single-key configs, never one
        //fused config.
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachMap')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Map('first', ['a' => '1', 'b' => '2', 'c' => '3']), 'prj');
                $callback(new Map('second', ['x' => '10', 'y' => '20']), 'prj');

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
                'configs' => [
                    'prj-first-map' => ['file' => './configs/prj-first-map'],
                    'prj-second-map' => ['file' => './configs/prj-second-map'],
                ],
            ],
            $generation->getComposeFile(),
        );

        $files = $generation->getFiles();
        //Each map keeps all of its own keys, grouped in its own file; the two maps are not merged.
        self::assertSame("a=1\nb=2\nc=3", $files['configs/prj-first-map']);
        self::assertSame("x=10\ny=20", $files['configs/prj-second-map']);
        foreach (array_keys($files) as $path) {
            self::assertStringNotContainsString('__', $path);
        }
    }
}
