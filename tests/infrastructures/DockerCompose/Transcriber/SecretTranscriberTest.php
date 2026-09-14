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
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\SecretTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

use function base64_encode;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SecretTranscriber::class)]
class SecretTranscriberTest extends TestCase
{
    private function buildTranscriber(): SecretTranscriber
    {
        return new SecretTranscriber();
    }

    public function testTranscribe(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachSecret')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                //Single-key map secret
                $callback(new Secret('db', 'map', ['password' => 'p4ss']), 'prj');
                //Multi-key map secret, with a base64 encoded value
                $callback(
                    new Secret(
                        'tls',
                        'map',
                        ['tls.crt' => 'CERT', 'tls.key' => 'base64:' . base64_encode('KEY')],
                        'tls',
                    ),
                    'prj',
                );
                //Non-map provider secret is ignored
                $callback(new Secret('vault', 'vault', ['token' => 'abc']), 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->exactly(2))->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            SecretTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                accumulator: $generation,
                promise: $promise,
                defaultsBag: $this->createStub(DefaultsBag::class),
                namespace: 'default',
            ),
        );

        //One Compose secret per key, each backed by its own file (mountable like a Kubernetes Secret),
        //base64 values decoded.
        self::assertSame(
            [
                'secrets' => [
                    'prj-db-secret-password' => ['file' => './secrets/prj-db-secret/password'],
                    'prj-tls-secret-tls.crt' => ['file' => './secrets/prj-tls-secret/tls.crt'],
                    'prj-tls-secret-tls.key' => ['file' => './secrets/prj-tls-secret/tls.key'],
                ],
            ],
            $generation->getComposeFile(),
        );

        self::assertSame(
            [
                'secrets/prj-db-secret/password' => 'p4ss',
                'secrets/prj-tls-secret/tls.crt' => 'CERT',
                'secrets/prj-tls-secret/tls.key' => 'KEY',
            ],
            $generation->getFiles(),
        );
    }

    public function testTranscribeIgnoresNonMapProvider(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachSecret')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Secret('vault', 'vault', ['token' => 'abc']), 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame([], $generation->getComposeFile());
    }


    public function testTranscribeFailure(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachSecret')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Secret('db', 'map', ['password' => 'p4ss']), 'prj');

                return $cd;
            });

        $accumulator = $this->createMock(AccumulatorInterface::class);
        $accumulator->expects($this->once())
            ->method('addSecret')
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


    public function testTranscribeJoinsArrayValuesAndDecodesEachItem(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachSecret')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(
                    new Secret('multi', 'map', ['lines' => ['first', 'base64:' . base64_encode('second'), null]]),
                    '',
                );

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

        self::assertSame("first\nsecond\n", $generation->getFiles()['secrets/multi-secret/lines']);
    }
}
