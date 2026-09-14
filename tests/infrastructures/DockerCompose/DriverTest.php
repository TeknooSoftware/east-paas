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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\Yaml\Yaml;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerFactoryInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\GenericTranscriberInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver\Generator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver\Running;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\GeneratorStateException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;
use Traversable;
use TypeError;

use function array_key_first;
use function basename;
use function explode;
use function json_encode;
use function preg_match;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Driver::class)]
#[CoversClass(Generator::class)]
#[CoversClass(Running::class)]
class DriverTest extends TestCase
{
    private const WORKSPACE_ROOT = '/workspace';

    private function buildDriver(
        ?RunnerFactoryInterface $runnerFactory = null,
        ?TranscriberCollectionInterface $transcribers = null,
        ?FilesystemOperator $workspaceFilesystem = null,
        ?FilesystemOperator $templatesFilesystem = null,
    ): Driver {
        if (null === $templatesFilesystem) {
            $templatesFilesystem = $this->createStub(FilesystemOperator::class);
            $templatesFilesystem->method('read')->willReturn('playbook: {% project %}');
        }

        return new Driver(
            runnerFactory: $runnerFactory ?? $this->createStub(RunnerFactoryInterface::class),
            transcribers: $transcribers ?? $this->createStub(TranscriberCollectionInterface::class),
            workspaceFilesystem: $workspaceFilesystem ?? $this->createStub(FilesystemOperator::class),
            templatesFilesystem: $templatesFilesystem,
            workspaceRoot: self::WORKSPACE_ROOT,
            tmpDirFactory: static fn (): string => 'run-' . uniqid('', true),
            templates: [
                'deploy' => 'deploy.yml.template',
                'expose' => 'expose.yml.template',
            ],
            deployRoot: '/opt/paas',
            networkDriver: 'bridge',
            traefikContainer: 'traefik',
        );
    }

    public function testConfigureWrongUrl(): void
    {
        $this->expectException(TypeError::class);
        $this->buildDriver()->configure(
            new stdClass(),
            $this->createStub(IdentityInterface::class),
            $this->createStub(DefaultsBag::class),
            'namespace',
            false,
        );
    }

    public function testConfigureIdentityNotSupported(): void
    {
        $this->expectException(RuntimeException::class);
        $this->buildDriver()->configure(
            'ssh://host',
            $this->createStub(IdentityInterface::class),
            $this->createStub(DefaultsBag::class),
            'namespace',
            false,
        );
    }

    public function testConfigure(): void
    {
        self::assertInstanceOf(
            Driver::class,
            $this->buildDriver()->configure(
                'ssh://host',
                $this->createStub(ClusterCredentials::class),
                $this->createStub(DefaultsBag::class),
                'namespace',
                false,
            ),
        );
    }

    public function testDeployWithoutConfigurationRefuses(): void
    {
        $promise = $this->createStub(PromiseInterface::class);

        $this->expectException(GeneratorStateException::class);
        $this->buildDriver()->deploy(
            $this->createStub(CompiledDeploymentInterface::class),
            $promise,
        );
    }

    public function testDeployIteratesDeployTranscribersAndInvokesRunner(): void
    {
        $generic = $this->createMock(GenericTranscriberInterface::class);
        $generic->expects($this->once())->method('transcribe')->willReturnSelf();
        $deployment = $this->createMock(DeploymentInterface::class);
        $deployment->expects($this->once())->method('transcribe')->willReturnSelf();
        $exposing = $this->createMock(ExposingInterface::class);
        $exposing->expects($this->never())->method('transcribe');

        $transcribers = $this->createStub(TranscriberCollectionInterface::class);
        $transcribers->method('getIterator')->willReturnCallback(
            function () use ($generic, $deployment, $exposing): Traversable {
                yield from [$generic, $deployment, $exposing];
            }
        );

        $runner = $this->createMock(RunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (...$args) use ($runner): RunnerInterface {
                $args[4]->success('PLAY RECAP ok');

                return $runner;
            });

        $runnerFactory = $this->createMock(RunnerFactoryInterface::class);
        $runnerFactory->expects($this->once())
            ->method('__invoke')
            ->willReturn($runner);

        $cd = $this->createStub(CompiledDeploymentInterface::class);
        $cd->method('withJobSettings')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(1.0, 'prefix', 'my-project');

                return $cd;
            }
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $driver = $this->buildDriver($runnerFactory, $transcribers)->configure(
            'ssh://deployer@host:2222',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        );

        self::assertInstanceOf(Driver::class, $driver->deploy($cd, $promise));
    }

    public function testDeployFailsWhenRunnerFails(): void
    {
        $transcribers = $this->createStub(TranscriberCollectionInterface::class);
        $transcribers->method('getIterator')->willReturnCallback(
            function (): Traversable {
                yield from [];
            }
        );

        $runner = $this->createStub(RunnerInterface::class);
        $runner->method('run')->willReturnCallback(function (...$args) use ($runner): RunnerInterface {
            $args[4]->fail(new RuntimeException('boom'));

            return $runner;
        });

        $runnerFactory = $this->createStub(RunnerFactoryInterface::class);
        $runnerFactory->method('__invoke')->willReturn($runner);

        $cd = $this->createStub(CompiledDeploymentInterface::class);
        $cd->method('withJobSettings')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(1.0, 'prefix', 'my-project');

                return $cd;
            }
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $driver = $this->buildDriver($runnerFactory, $transcribers)->configure(
            'ssh://host',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        );

        $driver->deploy($cd, $promise);
    }

    public function testExposeIteratesExposingTranscribersOnly(): void
    {
        $generic = $this->createMock(GenericTranscriberInterface::class);
        $generic->expects($this->never())->method('transcribe');
        $deployment = $this->createMock(DeploymentInterface::class);
        $deployment->expects($this->never())->method('transcribe');
        $exposing = $this->createMock(ExposingInterface::class);
        $exposing->expects($this->once())->method('transcribe')->willReturnSelf();

        $transcribers = $this->createStub(TranscriberCollectionInterface::class);
        $transcribers->method('getIterator')->willReturnCallback(
            function () use ($generic, $deployment, $exposing): Traversable {
                yield from [$generic, $deployment, $exposing];
            }
        );

        $runner = $this->createStub(RunnerInterface::class);
        $runner->method('run')->willReturnCallback(function (...$args) use ($runner): RunnerInterface {
            $args[4]->success('ok');

            return $runner;
        });

        $runnerFactory = $this->createStub(RunnerFactoryInterface::class);
        $runnerFactory->method('__invoke')->willReturn($runner);

        $cd = $this->createStub(CompiledDeploymentInterface::class);
        $cd->method('withJobSettings')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(1.0, 'prefix', 'my-project');

                return $cd;
            }
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');

        $driver = $this->buildDriver($runnerFactory, $transcribers)->configure(
            'ssh://host',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        );

        self::assertInstanceOf(Driver::class, $driver->expose($cd, $promise));
    }

    public function testExposeSerializesTraefikConfigToProjectFile(): void
    {
        $exposing = $this->createMock(ExposingInterface::class);
        $exposing->expects($this->once())
            ->method('transcribe')
            ->willReturnCallback(function (...$args) use ($exposing): ExposingInterface {
                /** @var \Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface $accumulator */
                $accumulator = $args[1];
                $accumulator->addTraefikRouter('http', 'web', [
                    'rule' => 'Host(`demo.example.com`)',
                    'entryPoints' => ['web'],
                    'service' => 'web-default',
                ]);

                return $exposing;
            });

        $transcribers = $this->createStub(TranscriberCollectionInterface::class);
        $transcribers->method('getIterator')->willReturnCallback(
            function () use ($exposing): Traversable {
                yield from [$exposing];
            }
        );

        $runner = $this->createStub(RunnerInterface::class);
        $runner->method('run')->willReturnCallback(
            function (...$args) use ($runner): RunnerInterface {
                $args[4]->success('PLAY RECAP ok');

                return $runner;
            }
        );

        $writes = [];
        $workspaceFilesystem = $this->createMock(FilesystemOperator::class);
        $workspaceFilesystem->expects($this->atLeastOnce())
            ->method('write')
            ->willReturnCallback(
            function (string $path, string $content) use (&$writes): void {
                $writes[$path] = $content;
            }
        );
        //The per-run working directory (secrets, keys, inventory) is removed once the playbook has run
        $workspaceFilesystem->expects($this->once())
            ->method('deleteDirectory')
            ->with($this->matchesRegularExpression('#^run-[^/]+$#'));

        $runnerFactory = $this->createStub(RunnerFactoryInterface::class);
        $runnerFactory->method('__invoke')->willReturn($runner);

        $cd = $this->createStub(CompiledDeploymentInterface::class);
        $cd->method('withJobSettings')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(1.0, 'prefix', 'my-project');

                return $cd;
            }
        );

        $captured = null;
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->willReturnCallback(function (array $result) use (&$captured, $promise): PromiseInterface {
                $captured = $result;

                return $promise;
            });

        $driver = $this->buildDriver(
            $runnerFactory,
            $transcribers,
            $workspaceFilesystem,
        )->configure(
            'ssh://host',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        );

        $driver->expose($cd, $promise);

        $expectedTraefik = [
            'http' => [
                'routers' => [
                    'web' => [
                        'rule' => 'Host(`demo.example.com`)',
                        'entryPoints' => ['web'],
                        'service' => 'web-default',
                    ],
                ],
            ],
        ];

        //The Traefik dynamic configuration must be serialized to a per-project "<project>.yml" file inside
        //the run working dir, distinct from compose.yaml / expose.yml / inventory.ini.
        $traefikDump = Yaml::dump($expectedTraefik, 8, 4);
        $traefikPath = null;
        foreach ($writes as $path => $content) {
            if (
                $content === $traefikDump
                && 1 === preg_match('#^run-[^/]+/[a-z0-9-]+\.yml$#', $path)
            ) {
                $traefikPath = $path;
                break;
            }
        }

        self::assertNotNull(
            $traefikPath,
            'Expected the Traefik dynamic configuration to be written to a per-project .yml file',
        );

        self::assertIsArray($captured);
        self::assertArrayHasKey('traefik', $captured);
        self::assertSame($expectedTraefik, $captured['traefik']);
    }

    /**
     * @return array{0: RunnerFactoryInterface, 1: array<string, string>}
     */
    private function buildCapturingRunnerFactory(mixed $runnerOutput, array &$writes): RunnerFactoryInterface
    {
        $runner = $this->createStub(RunnerInterface::class);
        $runner->method('run')->willReturnCallback(
            function (...$args) use ($runner, $runnerOutput): RunnerInterface {
                $args[4]->success($runnerOutput);

                return $runner;
            }
        );

        $runnerFactory = $this->createStub(RunnerFactoryInterface::class);
        $runnerFactory->method('__invoke')->willReturn($runner);

        return $runnerFactory;
    }

    private function buildCompiledDeploymentStub(): CompiledDeploymentInterface
    {
        $cd = $this->createStub(CompiledDeploymentInterface::class);
        $cd->method('withJobSettings')->willReturnCallback(
            function (callable $callback) use ($cd): CompiledDeploymentInterface {
                $callback(1.0, 'prefix', 'my-project');

                return $cd;
            }
        );

        return $cd;
    }

    public function testDeployFailsWhenTheJobSettingsDoNotResolveAnAccumulator(): void
    {
        //withJobSettings never calls back: no Accumulator can be built
        $cd = $this->createStub(CompiledDeploymentInterface::class);
        $cd->method('withJobSettings')->willReturnSelf();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->isInstanceOf(InvalidConfigurationException::class));

        $driver = $this->buildDriver()->configure(
            'ssh://host',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        );

        $driver->deploy($cd, $promise);
    }

    public function testExposeFailsWhenTheStageTemplateIsMissing(): void
    {
        $transcribers = $this->createStub(TranscriberCollectionInterface::class);
        $transcribers->method('getIterator')->willReturnCallback(
            function (): Traversable {
                yield from [];
            }
        );

        $driver = new Driver(
            runnerFactory: $this->createStub(RunnerFactoryInterface::class),
            transcribers: $transcribers,
            workspaceFilesystem: $this->createStub(FilesystemOperator::class),
            templatesFilesystem: $this->createStub(FilesystemOperator::class),
            workspaceRoot: self::WORKSPACE_ROOT,
            tmpDirFactory: static fn (): string => 'run',
            templates: ['deploy' => 'deploy.yml.template'],
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->isInstanceOf(InvalidConfigurationException::class));

        $driver->configure(
            'ssh://host',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        )->expose($this->buildCompiledDeploymentStub(), $promise);
    }

    public function testDeployFailsWhenATranscriberFails(): void
    {
        $deployment = $this->createMock(DeploymentInterface::class);
        $deployment->expects($this->once())
            ->method('transcribe')
            ->willReturnCallback(function (...$args) use ($deployment): DeploymentInterface {
                $args[2]->fail(new RuntimeException('transcription failed'));

                return $deployment;
            });

        $transcribers = $this->createStub(TranscriberCollectionInterface::class);
        $transcribers->method('getIterator')->willReturnCallback(
            function () use ($deployment): Traversable {
                yield from [$deployment];
            }
        );

        $runnerFactory = $this->createMock(RunnerFactoryInterface::class);
        $runnerFactory->expects($this->never())->method('__invoke');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(
                static fn (RuntimeException $error): bool => 'transcription failed' === $error->getMessage(),
            ));

        $this->buildDriver($runnerFactory, $transcribers)->configure(
            'ssh://host',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        )->deploy($this->buildCompiledDeploymentStub(), $promise);
    }

    public function testDeployWritesTheAccumulatedFilesAndReportsWarningsAndStructuredOutput(): void
    {
        $deployment = $this->createMock(DeploymentInterface::class);
        $deployment->expects($this->once())
            ->method('transcribe')
            ->willReturnCallback(function (...$args) use ($deployment): DeploymentInterface {
                $args[1]
                    ->addService('php', ['image' => 'php', 'profiles' => ['jobs']])
                    ->addVolume('cache', ['driver' => 'local', 'x-paas-reset' => true])
                    ->addFile('secrets/php.env', 'KEY="value"')
                    ->addWarning('something partial');

                return $deployment;
            });

        $transcribers = $this->createStub(TranscriberCollectionInterface::class);
        $transcribers->method('getIterator')->willReturnCallback(
            function () use ($deployment): Traversable {
                yield from [$deployment];
            }
        );

        $writes = [];
        $workspaceFilesystem = $this->createMock(FilesystemOperator::class);
        $workspaceFilesystem->method('write')->willReturnCallback(
            function (string $path, string $content) use (&$writes): void {
                $writes[$path] = $content;
            }
        );
        $workspaceFilesystem->expects($this->once())->method('deleteDirectory');

        $templatesFilesystem = $this->createStub(FilesystemOperator::class);
        $templatesFilesystem->method('read')->willReturn(
            "project: {% project %}\nfiles: {% paasFiles %}\nvolumes: {% paasResetVolumes %}\njobs: {% paasJobs %}",
        );

        $captured = null;
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->willReturnCallback(function (array $result) use (&$captured, $promise): PromiseInterface {
                $captured = $result;

                return $promise;
            });
        $promise->expects($this->never())->method('fail');

        $this->buildDriver(
            $this->buildCapturingRunnerFactory(['recap' => 'ok=3'], $writes),
            $transcribers,
            $workspaceFilesystem,
            $templatesFilesystem,
        )->configure(
            'docker.example.com:2200',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        )->deploy($this->buildCompiledDeploymentStub(), $promise);

        //The accumulated file is written into the run working dir, the playbook embeds its absolute path,
        //the reset volumes and the job runs; the inventory is built from a scheme-less address.
        $workingDirs = [];
        foreach ($writes as $path => $content) {
            $workingDirs[explode('/', $path, 2)[0]] = true;
        }

        self::assertCount(1, $workingDirs);
        $workingDir = (string) array_key_first($workingDirs);

        self::assertSame('KEY="value"', $writes[$workingDir . '/secrets/php.env']);
        self::assertSame(
            "[docker_host]\ndocker.example.com ansible_host=docker.example.com ansible_port=2200\n",
            $writes[$workingDir . '/inventory.ini'],
        );
        self::assertSame(
            "project: default-prefix-my-project\n"
            . 'files: ' . json_encode([[
                'src' => self::WORKSPACE_ROOT . '/' . $workingDir . '/secrets/php.env',
                'dest' => 'secrets/php.env',
                'mode' => '0600',
            ]]) . "\n"
            . 'volumes: ["cache"]' . "\n"
            . 'jobs: [{"service":"php","run":1,"completions":1,"timeout":0,"ok_codes":[0]}]',
            $writes[$workingDir . '/deploy.yml'],
        );

        self::assertIsArray($captured);
        self::assertSame('{"recap":"ok=3"}', $captured['output']);
        self::assertSame(['something partial'], $captured['warnings']);
    }

    public function testInventoryUsesTheDefaultSshPortForABareHost(): void
    {
        $transcribers = $this->createStub(TranscriberCollectionInterface::class);
        $transcribers->method('getIterator')->willReturnCallback(
            function (): Traversable {
                yield from [];
            }
        );

        $writes = [];
        $workspaceFilesystem = $this->createStub(FilesystemOperator::class);
        $workspaceFilesystem->method('write')->willReturnCallback(
            function (string $path, string $content) use (&$writes): void {
                $writes[basename($path)] = $content;
            }
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');

        $this->buildDriver(
            $this->buildCapturingRunnerFactory('PLAY RECAP ok', $writes),
            $transcribers,
            $workspaceFilesystem,
        )->configure(
            'docker.example.com',
            $this->createStub(ClusterCredentials::class),
            $this->createStub(DefaultsBag::class),
            'default',
            false,
        )->deploy($this->buildCompiledDeploymentStub(), $promise);

        self::assertSame(
            "[docker_host]\ndocker.example.com ansible_host=docker.example.com ansible_port=22\n",
            $writes['inventory.ini'],
        );
    }
}
