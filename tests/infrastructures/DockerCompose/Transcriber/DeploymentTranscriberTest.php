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
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheck;
use Teknoo\East\Paas\Compilation\CompiledDeployment\HealthCheckType;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\MapReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod\RestartPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;
use Teknoo\East\Paas\Compilation\CompiledDeployment\SecretReference;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\PersistentVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\MapVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\SecretVolume;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\DeploymentTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

use function base64_encode;

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
                        'networks' => ['default-prj-private'],
                        'expose' => [9000],
                        'environment' => ['APP_ENV' => 'prod'],
                        'restart' => 'always',
                    ],
                ],
                'networks' => [
                    'default-prj-private' => [
                        'name' => 'default-prj-private',
                        'driver' => 'bridge',
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

        self::assertSame(['default-prj-private'], $services['web']['networks']);
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

    /**
     * @return array{0: CompiledDeploymentInterface, 1: Accumulator, 2: PromiseInterface}
     */
    private function prepareWithSecretsAndMaps(Pod $pod, bool $expectSuccess = true): array
    {
        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->method('foreachSecret')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Secret('db', 'map', ['password' => 'p4$$', 'user' => 'base64:' . base64_encode('bob')]), 'prj');
                $callback(new Secret('vault', 'vault', ['token' => 'abc']), 'prj');

                return $cd;
            },
        );
        $cd->method('foreachMap')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(new Map('app', ['DEBUG' => 'true', 'MOTD' => "hello \"world\"\nbye"]), 'prj');

                return $cd;
            },
        );
        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd, $pod): CompiledDeploymentInterface {
                $callback($pod, [], [], 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        if ($expectSuccess) {
            $promise->expects($this->once())->method('success');
            $promise->expects($this->never())->method('fail');
        }

        return [$cd, $generation, $promise];
    }

    public function testTranscribeVariablesFromSecretsAndMapsGoToAnEnvFile(): void
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
                    variables: [
                        'APP_ENV' => 'prod',
                        'DB_PASSWORD' => new SecretReference('db', 'password', false),
                        'db' => new SecretReference('db', null, true),
                        'MOTD' => new MapReference('app', 'MOTD', false),
                        'app' => new MapReference('app', null, true),
                        'MISSING' => new MapReference('app', 'nope', false),
                    ],
                ),
            ],
        );

        [$cd, $generation, $promise] = $this->prepareWithSecretsAndMaps($pod);

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        $service = $generation->getComposeFile()['services']['php'];
        self::assertSame(['APP_ENV' => 'prod'], $service['environment']);
        self::assertSame(['./secrets/php-php-run.env'], $service['env_file']);
        self::assertArrayNotHasKey('secrets', $service);
        self::assertArrayNotHasKey('configs', $service);

        //Values are double-quoted, `$` doubled (no Compose interpolation), quotes/newlines escaped, a missing
        //key yields an empty variable; the file lives under secrets/ (pushed with mode 0600).
        self::assertSame(
            'DB_PASSWORD="p4$$$$"' . "\n"
            . 'password="p4$$$$"' . "\n"
            . 'user="bob"' . "\n"
            . 'MOTD="hello \\"world\\"\\nbye"' . "\n"
            . 'DEBUG="true"' . "\n"
            . 'MISSING=""' . "\n",
            $generation->getFiles()['secrets/php-php-run.env'],
        );
    }

    public function testTranscribeSecretAndMapVolumesAreMountedPerKey(): void
    {
        $pod = new Pod(
            name: 'php',
            replicas: 1,
            containers: [
                new Container(
                    name: 'php-run',
                    image: 'registry/php',
                    version: '8.3',
                    listen: [],
                    volumes: [
                        'vault' => new SecretVolume('vault', '/vault/', 'db'),
                        'conf' => new MapVolume('conf', '/etc/app', 'app'),
                    ],
                    variables: [],
                ),
            ],
        );

        [$cd, $generation, $promise] = $this->prepareWithSecretsAndMaps($pod);

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        $service = $generation->getComposeFile()['services']['php'];
        self::assertSame(
            [
                ['source' => 'prj-db-secret-password', 'target' => '/vault/password'],
                ['source' => 'prj-db-secret-user', 'target' => '/vault/user'],
            ],
            $service['secrets'],
        );
        self::assertSame(
            [
                ['source' => 'prj-app-map-DEBUG', 'target' => '/etc/app/DEBUG'],
                ['source' => 'prj-app-map-MOTD', 'target' => '/etc/app/MOTD'],
            ],
            $service['configs'],
        );
        self::assertArrayNotHasKey('env_file', $service);
    }

    public function testTranscribeFailsOnNonMapSecretReference(): void
    {
        $pod = new Pod(
            name: 'php',
            replicas: 1,
            containers: [
                new Container('php-run', 'registry/php', '8.3', [], [], [
                    'TOKEN' => new SecretReference('vault', 'token', false),
                ]),
            ],
        );

        [$cd, $generation, $promise] = $this->prepareWithSecretsAndMaps($pod, false);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->isInstanceOf(InvalidConfigurationException::class));

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }

    public function testTranscribeFailsOnUnknownSecretVolume(): void
    {
        $pod = new Pod(
            name: 'php',
            replicas: 1,
            containers: [
                new Container('php-run', 'registry/php', '8.3', [], [
                    'vault' => new SecretVolume('vault', '/vault', 'vault'),
                ], []),
            ],
        );

        [$cd, $generation, $promise] = $this->prepareWithSecretsAndMaps($pod, false);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->isInstanceOf(InvalidConfigurationException::class));

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }

    public function testTranscribeResourcesAndReplicasUseComposeKeys(): void
    {
        $resources = new ResourceSet([
            new Resource('cpu', '250m', '1'),
            new Resource('memory', '64Mi', '1.5Gi'),
            new Resource('nvidia.com/gpu', '1', '1'),
        ]);

        $pod = new Pod(
            name: 'web',
            replicas: 3,
            containers: [
                new Container('nginx', 'registry/nginx', '1.27', [80], [], [], null, $resources),
                new Container('waf', 'registry/waf', '1.0', [], [], [], null, $resources),
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
        //Kubernetes quantities converted to Compose ones (`cpus` decimal, binary suffix without `i`), the
        //unsupported resource types skipped; replicas only on the anchor (sidecars share its namespace).
        self::assertSame(
            [
                'resources' => [
                    'reservations' => ['cpus' => '0.25', 'memory' => '64M'],
                    'limits' => ['cpus' => '1', 'memory' => '1.5G'],
                ],
                'replicas' => 3,
            ],
            $services['web']['deploy'],
        );
        self::assertSame(
            [
                'resources' => [
                    'reservations' => ['cpus' => '0.25', 'memory' => '64M'],
                    'limits' => ['cpus' => '1', 'memory' => '1.5G'],
                ],
            ],
            $services['web-waf']['deploy'],
        );
    }


    public function testTranscribeBuiltImagesHealthChecksRestartPolicyAndFsGroup(): void
    {
        $image = new Image('php', '/images/php', false, '8.3-abc', [])->withRegistry('https://reg.example');

        $pod = new Pod(
            name: 'web',
            replicas: 2,
            containers: [
                new Container(
                    'php',
                    'php',
                    '8.3',
                    [9000],
                    [],
                    [],
                    new HealthCheck(5, 10, HealthCheckType::Http, null, 9000, '/status', true, 1, 3),
                ),
                new Container(
                    'nginx',
                    'registry/nginx',
                    '1.27',
                    [80],
                    [],
                    [],
                    new HealthCheck(5, 10, HealthCheckType::Tcp, null, 80, null, null, 1, 2),
                ),
                new Container(
                    'cron',
                    'registry/cron',
                    '1',
                    [],
                    [],
                    [],
                    new HealthCheck(5, 10, HealthCheckType::Command, ['pgrep', 'cron'], null, null, null, 1, 1),
                ),
            ],
            fsGroup: 1000,
            restartPolicy: RestartPolicy::OnFailure,
        );

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd, $pod, $image): CompiledDeploymentInterface {
                $callback($pod, ['php' => ['8.3' => $image]], [], '');

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

        self::assertSame('reg.example/php:8.3-abc', $services['web']['image']);
        self::assertSame(
            [
                'test' => ['CMD-SHELL', 'curl -fk https://localhost:9000/status || exit 1'],
                'start_period' => '5s',
                'interval' => '10s',
                'timeout' => '5s',
                'retries' => 3,
            ],
            $services['web']['healthcheck'],
        );
        self::assertSame(['replicas' => 2], $services['web']['deploy']);
        self::assertSame('on-failure', $services['web']['restart']);
        self::assertSame(['1000'], $services['web']['group_add']);

        self::assertSame(
            ['CMD-SHELL', 'nc -z localhost 80 || exit 1'],
            $services['web-nginx']['healthcheck']['test'],
        );
        self::assertSame(['CMD-SHELL', 'pgrep cron'], $services['web-cron']['healthcheck']['test']);
        //Sidecars share the anchor's network namespace: no replicas (nor any deploy block) of their own
        self::assertArrayNotHasKey('deploy', $services['web-nginx']);
        self::assertArrayNotHasKey('deploy', $services['web-cron']);
    }

    public function testTranscribeNeverRestartPolicyPersistentVolumeAndMemoryWithoutBinarySuffix(): void
    {
        $containerVolume = new Volume(name: 'extra', paths: ['data'], localPath: '/data', mountPath: '/opt/extra');

        $pod = new Pod(
            name: 'php',
            replicas: 1,
            containers: [
                new Container(
                    name: 'php-run',
                    image: 'registry/php',
                    version: '8.3',
                    listen: [],
                    volumes: [
                        'data' => new PersistentVolume('data', '/var/data', 'local', '5Gi'),
                        'extra' => $containerVolume,
                    ],
                    variables: [],
                    resources: new ResourceSet([new Resource('memory', '128M', '256M')]),
                ),
            ],
            restartPolicy: RestartPolicy::Never,
        );

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachPod')
            ->willReturnCallback(function (callable $callback) use ($cd, $pod): CompiledDeploymentInterface {
                //A deployment-level entry that is not a populated Volume: the container's own volume is used
                $callback($pod, [], ['php-run_extra' => $this->createStub(VolumeInterface::class)], 'prj');

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

        $compose = $generation->getComposeFile();
        self::assertSame(
            ['prj-data:/var/data', 'prj-extra-volume:/opt/extra:ro'],
            $compose['services']['php']['volumes'],
        );
        self::assertSame('no', $compose['services']['php']['restart']);
        self::assertSame(
            ['resources' => ['reservations' => ['memory' => '128M'], 'limits' => ['memory' => '256M']]],
            $compose['services']['php']['deploy'],
        );
        self::assertSame('extra', $compose['services']['prj-extra-volume-init']['image']);
    }

    public function testTranscribeFailsOnUnknownMapVolume(): void
    {
        $pod = new Pod(
            name: 'php',
            replicas: 1,
            containers: [
                new Container('php-run', 'registry/php', '8.3', [], [
                    'conf' => new MapVolume('conf', '/etc/app', 'nope'),
                ], []),
            ],
        );

        [$cd, $generation, $promise] = $this->prepareWithSecretsAndMaps($pod, false);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->isInstanceOf(InvalidConfigurationException::class));

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );
    }
}
