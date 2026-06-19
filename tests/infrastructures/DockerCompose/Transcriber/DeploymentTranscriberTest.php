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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod\RestartPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\DeploymentTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DeploymentTranscriber::class)]
class DeploymentTranscriberTest extends TestCase
{
    private function buildTranscriber(): DeploymentTranscriber
    {
        return new DeploymentTranscriber();
    }

    public function testTranscribeSingleContainerPod(): void
    {
        $pod = new Pod(
            name: 'php',
            replicas: 1,
            containers: [
                new Container(
                    name: 'php-run',
                    image: 'registry/php',
                    version: '8.3',
                    listen: [9000],
                    volumes: [],
                    variables: ['APP_ENV' => 'prod'],
                ),
            ],
            restartPolicy: RestartPolicy::Always,
        );

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd, $pod): CompiledDeploymentInterface {
                $callback($pod, [], [], 'prj');

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
                'services' => [
                    'php' => [
                        'image' => 'registry/php:8.3',
                        'networks' => ['default-prj_private'],
                        'expose' => [9000],
                        'environment' => ['APP_ENV' => 'prod'],
                        'restart' => 'always',
                    ],
                ],
                'networks' => [
                    'default-prj_private' => [
                        'name' => 'default-prj_private',
                        'driver' => 'bridge',
                        'internal' => true,
                    ],
                ],
            ],
            $generation->getComposeFile(),
        );
    }

    public function testTranscribeMultiContainerPodSharesNetwork(): void
    {
        $pod = new Pod(
            name: 'web',
            replicas: 1,
            containers: [
                new Container('nginx', 'registry/nginx', '1.27', [80], [], []),
                new Container('waf', 'registry/waf', '1.0', [], [], []),
            ],
        );

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd, $pod): CompiledDeploymentInterface {
                $callback($pod, [], [], 'prj');

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

        $services = $generation->getComposeFile()['services'];

        self::assertSame(['default-prj_private'], $services['web']['networks']);
        self::assertSame([80], $services['web']['expose']);
        self::assertArrayHasKey('web-waf', $services);
        self::assertSame('service:web', $services['web-waf']['network_mode']);
        self::assertArrayNotHasKey('networks', $services['web-waf']);
        self::assertArrayNotHasKey('expose', $services['web-waf']);
    }

    public function testTranscribePopulatedVolumeAddsInitService(): void
    {
        //The container's volume instance has no registry; the registry-qualified one is provided through
        //the foreachPod $volumes map (keyed <container>_<volumeKey>), mirroring CompiledDeployment.
        $containerVolume = new Volume(
            name: 'extra-app',
            paths: ['data'],
            localPath: '/data',
            mountPath: '/opt/extra',
        );
        $deploymentVolume = $containerVolume->withRegistry('https://reg.example');

        $pod = new Pod(
            name: 'php',
            replicas: 1,
            containers: [
                new Container(
                    name: 'php-run',
                    image: 'registry/php',
                    version: '8.3',
                    listen: [9000],
                    volumes: ['extra' => $containerVolume],
                    variables: [],
                ),
            ],
        );

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(
                function (callable $callback) use ($cd, $pod, $deploymentVolume): CompiledDeploymentInterface {
                    $callback($pod, [], ['php-run_extra' => $deploymentVolume], 'prj');

                    return $cd;
                },
            );

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

        $compose = $generation->getComposeFile();

        //Main service mounts the populated volume read-only and waits for the init service.
        self::assertSame(['prj-extra-app-volume:/opt/extra:ro'], $compose['services']['php']['volumes']);
        self::assertSame(
            ['prj-extra-app-volume-init' => ['condition' => 'service_completed_successfully']],
            $compose['services']['php']['depends_on'],
        );

        //Init service runs the volume image (registry from the deployment volume, scheme stripped) and
        //copies the baked data into the named volume at MOUNT_PATH.
        self::assertSame(
            [
                'image' => 'reg.example/extra-app',
                'environment' => ['MOUNT_PATH' => '/opt/extra'],
                'volumes' => ['prj-extra-app-volume:/opt/extra'],
                'network_mode' => 'none',
                'restart' => 'no',
            ],
            $compose['services']['prj-extra-app-volume-init'],
        );

        //The named volume is declared as a local volume.
        self::assertSame(['driver' => 'local'], $compose['volumes']['prj-extra-app-volume']);
    }
}
