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

use ArrayIterator;
use DI\Container;
use DI\ContainerBuilder;
use DomainException;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerFactoryInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\ConfigMapTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\DeploymentTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\IngressTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\JobTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\SecretTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\ServiceTranscriber;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\VolumeTranscriber;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @throws Exception
     */
    protected function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__ . '/../../../infrastructures/DockerCompose/di.php');

        return $containerDefinition->build();
    }

    public function testRunnerFactoryInterface(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set('teknoo.east.paas.docker-compose.ansible.binary', '/usr/local/bin/ansible-playbook');
        $container->set('teknoo.east.paas.docker-compose.timeout', 120);

        $this->assertInstanceOf(
            RunnerFactoryInterface::class,
            $container->get(RunnerFactoryInterface::class),
        );
    }

    public function testRunnerFactoryInterfaceWithDefaults(): void
    {
        $this->assertInstanceOf(
            RunnerFactoryInterface::class,
            $this->buildContainer()->get(RunnerFactoryInterface::class),
        );
    }

    /**
     * @return iterable<string, array{0: class-string}>
     */
    public static function transcribersProvider(): iterable
    {
        yield 'secret' => [SecretTranscriber::class];
        yield 'configMap' => [ConfigMapTranscriber::class];
        yield 'volume' => [VolumeTranscriber::class];
        yield 'deployment' => [DeploymentTranscriber::class];
        yield 'job' => [JobTranscriber::class];
        yield 'service' => [ServiceTranscriber::class];
        yield 'ingress' => [IngressTranscriber::class];
    }

    #[DataProvider('transcribersProvider')]
    public function testTranscribers(string $className): void
    {
        $this->assertInstanceOf($className, $this->buildContainer()->get($className));
    }

    #[DataProvider('transcribersProvider')]
    public function testTranscribersRefuseAForeignClass(string $className): void
    {
        $container = $this->buildContainer();
        $container->set($className . ':class', stdClass::class);

        $this->expectException(DomainException::class);
        $container->get($className);
    }

    public function testIngressTranscriberWithAllParameters(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.docker-compose.traefik.entrypoint.web', 'http');
        $container->set('teknoo.east.paas.docker-compose.traefik.entrypoint.websecure', 'https');
        $container->set('teknoo.east.paas.docker-compose.traefik.default_certresolver', 'letsencrypt');
        $container->set('teknoo.east.paas.docker-compose.https_backend.insecure_skip_verify', true);
        $container->set('teknoo.east.paas.docker-compose.ingress.default_service.name', 'front');
        $container->set('teknoo.east.paas.docker-compose.ingress.default_service.port', 8080);
        $container->set('teknoo.east.paas.docker-compose.traefik.default_middlewares', ['auth@file']);

        $this->assertInstanceOf(IngressTranscriber::class, $container->get(IngressTranscriber::class));
    }

    public function testIngressTranscriberWithIterableMiddlewares(): void
    {
        $container = $this->buildContainer();
        $container->set(
            'teknoo.east.paas.docker-compose.traefik.default_middlewares',
            new ArrayIterator(['auth@file']),
        );

        $this->assertInstanceOf(IngressTranscriber::class, $container->get(IngressTranscriber::class));
    }

    public function testIngressTranscriberRefusesNonIterableMiddlewares(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.docker-compose.traefik.default_middlewares', 'auth@file');

        $this->expectException(DomainException::class);
        $container->get(IngressTranscriber::class);
    }

    public function testTranscriberCollection(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(
            TranscriberCollectionInterface::class,
            $container->get(TranscriberCollectionInterface::class),
        );
    }

    public function testDriver(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');

        $this->assertInstanceOf(Driver::class, $container->get(Driver::class));
    }

    public function testDriverWithAllParameters(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set('teknoo.east.paas.docker-compose.deploy_root', '/srv/paas');
        $container->set('teknoo.east.paas.docker-compose.network.driver', 'overlay');
        $container->set('teknoo.east.paas.docker-compose.network.internal', true);
        $container->set('teknoo.east.paas.docker-compose.traefik.container', 'edge');
        $container->set('teknoo.east.paas.docker-compose.traefik.dynamic_dir', '/srv/traefik/dynamic');
        $container->set('teknoo.east.paas.docker-compose.traefik.certs_dir', '/srv/traefik/certs');
        $container->set('teknoo.east.paas.docker-compose.traefik.certs_mount_dir', '/certs');
        $container->set('teknoo.east.paas.docker-compose.files.modes', ['secrets/' => '0600', 'configs/' => '0640']);
        $container->set('teknoo.east.paas.docker-compose.files.default_mode', '0600');

        $this->assertInstanceOf(Driver::class, $container->get(Driver::class));
    }

    public function testDriverWithIterableFileModes(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set(
            'teknoo.east.paas.docker-compose.files.modes',
            new ArrayIterator(['secrets/' => '0600', 'configs/' => '0644']),
        );

        $this->assertInstanceOf(Driver::class, $container->get(Driver::class));
    }

    public static function invalidFileModesProvider(): iterable
    {
        yield 'not iterable' => ['teknoo.east.paas.docker-compose.files.modes', '0644'];
        yield 'empty prefix' => ['teknoo.east.paas.docker-compose.files.modes', ['' => '0644']];
        yield 'not a string prefix' => ['teknoo.east.paas.docker-compose.files.modes', ['0644']];
        yield 'not a string mode' => ['teknoo.east.paas.docker-compose.files.modes', ['configs/' => 0644]];
        yield 'not an octal mode' => ['teknoo.east.paas.docker-compose.files.modes', ['configs/' => '0648']];
        yield 'not a string default mode' => ['teknoo.east.paas.docker-compose.files.default_mode', 0640];
        yield 'not an octal default mode' => ['teknoo.east.paas.docker-compose.files.default_mode', 'rw-r-----'];
        yield 'default mode with a line break' => ['teknoo.east.paas.docker-compose.files.default_mode', "0640\n"];
    }

    #[DataProvider('invalidFileModesProvider')]
    public function testDriverRefusesInvalidFileModes(string $parameter, mixed $value): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');
        $container->set($parameter, $value);

        $this->expectException(DomainException::class);
        $container->get(Driver::class);
    }

    public function testDirectory(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.worker.tmp_dir', '/tmp');

        $this->assertInstanceOf(Directory::class, $container->get(Directory::class));
    }
}
