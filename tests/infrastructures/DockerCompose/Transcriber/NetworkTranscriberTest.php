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
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Generation;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\NetworkTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(NetworkTranscriber::class)]
class NetworkTranscriberTest extends TestCase
{
    private function buildTranscriber(): NetworkTranscriber
    {
        return new NetworkTranscriber('bridge');
    }

    public function testTranscribe(): void
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('withJobSettings')
            ->willReturnCallback(function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(1.0, 'a-prefix', 'my-project');

                return $cd;
            });

        $generation = new Generation('default-my-project', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            NetworkTranscriber::class,
            $this->buildTranscriber()->transcribe(
                compiledDeployment: $cd,
                generation: $generation,
                promise: $promise,
                defaultsBag: $this->createStub(DefaultsBag::class),
                namespace: 'default',
            ),
        );

        self::assertSame(
            [
                'networks' => [
                    'private' => [
                        'driver' => 'bridge',
                        'internal' => true,
                    ],
                ],
            ],
            $generation->getComposeFile(),
        );

        self::assertSame(
            ['default-my-project_private'],
            $generation->getNetworksToWire(),
        );
    }
}
