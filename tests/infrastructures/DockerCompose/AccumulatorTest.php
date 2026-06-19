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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\FileToCopy;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\InlineContent;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\MountedFile;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Accumulator::class)]
class AccumulatorTest extends TestCase
{
    private function buildAccumulator(): Accumulator
    {
        return new Accumulator('foo-bar', 'private', 'bridge');
    }

    public function testGetProjectName(): void
    {
        self::assertSame('foo-bar', $this->buildAccumulator()->getProjectName());
    }

    public function testGetDedicatedNetworkName(): void
    {
        self::assertSame('private', $this->buildAccumulator()->getDedicatedNetworkName());
    }

    public function testDefaultDedicatedNetworkName(): void
    {
        self::assertSame('private', new Accumulator('foo-bar')->getDedicatedNetworkName());
    }

    public function testGetNetworkName(): void
    {
        self::assertSame('foo-bar_private', $this->buildAccumulator()->getNetworkName());
    }

    public function testAddersAreFluent(): void
    {
        $accumulator = $this->buildAccumulator();

        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->addService('php', ['image' => 'php']));
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->publishPorts('php', ['80:8080']));
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->addVolume('data', []));
        self::assertInstanceOf(
            AccumulatorInterface::class,
            $accumulator->addConfig('cfg', new InlineContent('value')),
        );
        self::assertInstanceOf(
            AccumulatorInterface::class,
            $accumulator->addSecret('sec', new MountedFile('secrets/sec', 'value')),
        );
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->addTraefikRouter('http', 'r', []));
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->addTraefikService('http', 's', []));
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->addTlsCertificate('c.crt', 'c.key'));
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->setCertResolver('le'));
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->addFile('secrets/sec', 'value'));
    }

    public function testGetComposeFileShape(): void
    {
        $accumulator = $this->buildAccumulator();

        $accumulator
            ->addService('php', [
                'image' => 'php:8.4',
                'networks' => ['foo-bar_private'],
                'expose' => [9000],
            ])
            ->addVolume('data', [])
            ->addConfig('app-config', new InlineContent('KEY: value'))
            ->addSecret('app-secret', new MountedFile('secrets/app-secret', 'topsecret'));

        self::assertSame(
            [
                'services' => [
                    'php' => [
                        'image' => 'php:8.4',
                        'networks' => ['foo-bar_private'],
                        'expose' => [9000],
                    ],
                ],
                'networks' => [
                    'foo-bar_private' => [
                        'name' => 'foo-bar_private',
                        'driver' => 'bridge',
                        'internal' => true,
                    ],
                ],
                'volumes' => [
                    'data' => [],
                ],
                'configs' => [
                    'app-config' => ['content' => 'KEY: value'],
                ],
                'secrets' => [
                    'app-secret' => ['file' => './secrets/app-secret'],
                ],
            ],
            $accumulator->getComposeFile(),
        );
    }

    public function testGetComposeFileEmpty(): void
    {
        self::assertSame([], $this->buildAccumulator()->getComposeFile());
    }

    public function testPublishPortsMergesIntoExistingService(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('php', ['image' => 'php', 'ports' => ['80:80']])
            ->publishPorts('php', ['443:443']);

        $compose = $accumulator->getComposeFile();
        self::assertSame(['80:80', '443:443'], $compose['services']['php']['ports']);
    }

    public function testPublishPortsIgnoresEmpty(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addService('php', ['image' => 'php'])->publishPorts('php', []);

        $compose = $accumulator->getComposeFile();
        self::assertArrayNotHasKey('ports', $compose['services']['php']);
    }

    public function testAddConfigWithFileWritesFile(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addConfig('cfg', new MountedFile('configs/cfg', 'KEY: value'));

        self::assertSame(['configs/cfg' => 'KEY: value'], $accumulator->getFiles());
        self::assertSame(
            ['cfg' => ['file' => './configs/cfg']],
            $accumulator->getComposeFile()['configs'],
        );
    }

    public function testAddConfigInlineDoesNotWriteFile(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addConfig('cfg', new InlineContent('KEY: value'));

        self::assertSame([], $accumulator->getFiles());
        self::assertSame(
            ['cfg' => ['content' => 'KEY: value']],
            $accumulator->getComposeFile()['configs'],
        );
    }

    public function testAddSecretInlineIsMaterialisedToFile(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addSecret('sec', new InlineContent('topsecret'));

        self::assertSame(['secrets/sec' => 'topsecret'], $accumulator->getFiles());
        self::assertSame(
            ['sec' => ['file' => './secrets/sec']],
            $accumulator->getComposeFile()['secrets'],
        );
    }

    public function testGetTraefikConfigShape(): void
    {
        $accumulator = $this->buildAccumulator();

        $accumulator
            ->addTraefikRouter('http', 'app', [
                'rule' => 'Host(`example.com`)',
                'entryPoints' => ['websecure'],
                'service' => 'app-default',
                'tls' => [],
            ])
            ->addTraefikService('http', 'app-default', [
                'loadBalancer' => [
                    'servers' => [['url' => 'http://php:9000']],
                ],
            ])
            ->addTraefikRouter('tcp', 'db', [
                'rule' => 'HostSNI(`*`)',
                'service' => 'db',
            ])
            ->addTraefikService('tcp', 'db', [
                'loadBalancer' => [
                    'servers' => [['address' => 'db:5432']],
                ],
            ])
            ->addTlsCertificate('certs/example.crt', 'certs/example.key');

        self::assertSame(
            [
                'http' => [
                    'routers' => [
                        'app' => [
                            'rule' => 'Host(`example.com`)',
                            'entryPoints' => ['websecure'],
                            'service' => 'app-default',
                            'tls' => [],
                        ],
                    ],
                    'services' => [
                        'app-default' => [
                            'loadBalancer' => [
                                'servers' => [['url' => 'http://php:9000']],
                            ],
                        ],
                    ],
                ],
                'tcp' => [
                    'routers' => [
                        'db' => [
                            'rule' => 'HostSNI(`*`)',
                            'service' => 'db',
                        ],
                    ],
                    'services' => [
                        'db' => [
                            'loadBalancer' => [
                                'servers' => [['address' => 'db:5432']],
                            ],
                        ],
                    ],
                ],
                'tls' => [
                    'certificates' => [
                        [
                            'certFile' => 'certs/example.crt',
                            'keyFile' => 'certs/example.key',
                        ],
                    ],
                ],
            ],
            $accumulator->getTraefikConfig(),
        );
    }

    public function testGetTraefikConfigSkipsEmptySections(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addTraefikRouter('http', 'app', ['service' => 'app']);

        self::assertSame(
            [
                'http' => [
                    'routers' => [
                        'app' => ['service' => 'app'],
                    ],
                ],
            ],
            $accumulator->getTraefikConfig(),
        );
    }

    public function testGetTraefikConfigWithCertResolver(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->setCertResolver('letsencrypt');

        self::assertSame(
            [
                'tls' => [
                    'certResolver' => 'letsencrypt',
                ],
            ],
            $accumulator->getTraefikConfig(),
        );
    }

    public function testGetTraefikConfigEmpty(): void
    {
        self::assertSame([], $this->buildAccumulator()->getTraefikConfig());
    }

    public function testAddFile(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addFile('secrets/a', 'aaa')
            ->addFile('secrets/b', 'bbb');

        self::assertSame(
            [
                'secrets/a' => 'aaa',
                'secrets/b' => 'bbb',
            ],
            $accumulator->getFiles(),
        );
    }

    public function testGetFilesToCopyReturnsFileToCopyWithMode(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addFile('configs/cfg', 'value')
            ->addFile('secrets/sec', 'topsecret');

        $entries = $accumulator->getFilesToCopy();

        self::assertContainsOnlyInstancesOf(FileToCopy::class, $entries);
        self::assertCount(2, $entries);

        self::assertSame('configs/cfg', $entries[0]->src);
        self::assertSame('configs/cfg', $entries[0]->dest);
        self::assertSame('0640', $entries[0]->mode);

        self::assertSame('secrets/sec', $entries[1]->src);
        self::assertSame('secrets/sec', $entries[1]->dest);
        self::assertSame('0600', $entries[1]->mode);
    }

    public function testGetCertificatesToCopyReturnsFileToCopyWithoutMode(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addTlsCertificate('certs/example.crt', 'certs/example.key');

        $entries = $accumulator->getCertificatesToCopy();

        self::assertContainsOnlyInstancesOf(FileToCopy::class, $entries);
        self::assertCount(2, $entries);

        self::assertSame('certs/example.crt', $entries[0]->src);
        self::assertSame('example.crt', $entries[0]->dest);
        self::assertNull($entries[0]->mode);

        self::assertSame('certs/example.key', $entries[1]->src);
        self::assertSame('example.key', $entries[1]->dest);
        self::assertNull($entries[1]->mode);
    }

    public function testGetResetVolumes(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addVolume('data', ['driver' => 'local'])
            ->addVolume('cache', ['driver' => 'local', 'x-paas-reset' => true]);

        self::assertSame(['cache'], $accumulator->getResetVolumes());
    }

    public function testGetJobsToRun(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('php', ['image' => 'php'])
            ->addService('migrate', ['image' => 'php', 'profiles' => ['jobs']]);

        self::assertSame(['migrate'], $accumulator->getJobsToRun());
    }
}
