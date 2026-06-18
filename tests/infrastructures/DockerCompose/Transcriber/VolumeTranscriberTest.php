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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\VolumeTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(VolumeTranscriber::class)]
class VolumeTranscriberTest extends TestCase
{
    private function buildTranscriber(): VolumeTranscriber
    {
        return new VolumeTranscriber();
    }

    public function testTranscribe(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback('data', new PersistentVolume('data', '/var/data', 'local', '5Gi'), 'prj');
                $callback(
                    'reset',
                    new PersistentVolume('reset', '/var/reset', 'local', '1Gi', true),
                    'prj',
                );
                //Secret volumes are mounted on services, not declared as Compose volumes
                $callback('creds', new SecretVolume('creds', '/run/creds', 'my-secret'), 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            VolumeTranscriber::class,
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
                'volumes' => [
                    'prj-data' => ['driver' => 'local'],
                    'prj-reset' => ['driver' => 'local', 'x-paas-reset' => true],
                ],
            ],
            $generation->getComposeFile(),
        );
    }

    public function testTranscribeIgnoresNonPersistent(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachVolume')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback('creds', $this->createStub(VolumeInterface::class), 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame([], $generation->getComposeFile());
    }
}
