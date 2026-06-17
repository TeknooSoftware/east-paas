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
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Generation;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\FileToCopy;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Generation::class)]
class GenerationTest extends TestCase
{
    private function buildGeneration(): Generation
    {
        return new Generation('foo-bar', 'private');
    }

    public function testGetProjectName(): void
    {
        self::assertSame('foo-bar', $this->buildGeneration()->getProjectName());
    }

    public function testGetDedicatedNetworkName(): void
    {
        self::assertSame('private', $this->buildGeneration()->getDedicatedNetworkName());
    }

    public function testDefaultDedicatedNetworkName(): void
    {
        self::assertSame('private', new Generation('foo-bar')->getDedicatedNetworkName());
    }

    public function testAddersAreFluent(): void
    {
        $generation = $this->buildGeneration();

        self::assertInstanceOf(GenerationInterface::class, $generation->addService('php', ['image' => 'php']));
        self::assertInstanceOf(GenerationInterface::class, $generation->addNetwork('private', []));
        self::assertInstanceOf(GenerationInterface::class, $generation->addVolume('data', []));
        self::assertInstanceOf(GenerationInterface::class, $generation->addConfig('cfg', []));
        self::assertInstanceOf(GenerationInterface::class, $generation->addSecret('sec', []));
        self::assertInstanceOf(GenerationInterface::class, $generation->addTraefikRouter('http', 'r', []));
        self::assertInstanceOf(GenerationInterface::class, $generation->addTraefikService('http', 's', []));
        self::assertInstanceOf(GenerationInterface::class, $generation->addTlsCertificate('c.crt', 'c.key'));
        self::assertInstanceOf(GenerationInterface::class, $generation->setCertResolver('le'));
        self::assertInstanceOf(GenerationInterface::class, $generation->addFile('secrets/sec', 'value'));
        self::assertInstanceOf(GenerationInterface::class, $generation->wireNetworkToTraefik('foo-bar_private'));
    }

    public function testGetComposeFileShape(): void
    {
        $generation = $this->buildGeneration();

        $generation
            ->addNetwork('private', ['driver' => 'bridge', 'internal' => true])
            ->addService('php', [
                'image' => 'php:8.4',
                'networks' => ['private'],
                'expose' => [9000],
            ])
            ->addVolume('data', [])
            ->addConfig('app-config', ['content' => 'KEY: value'])
            ->addSecret('app-secret', ['file' => './secrets/app-secret']);

        self::assertSame(
            [
                'services' => [
                    'php' => [
                        'image' => 'php:8.4',
                        'networks' => ['private'],
                        'expose' => [9000],
                    ],
                ],
                'networks' => [
                    'private' => ['driver' => 'bridge', 'internal' => true],
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
            $generation->getComposeFile(),
        );
    }

    public function testGetComposeFileEmpty(): void
    {
        self::assertSame([], $this->buildGeneration()->getComposeFile());
    }

    public function testGetTraefikConfigShape(): void
    {
        $generation = $this->buildGeneration();

        $generation
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
            $generation->getTraefikConfig(),
        );
    }

    public function testGetTraefikConfigWithCertResolver(): void
    {
        $generation = $this->buildGeneration();
        $generation->setCertResolver('letsencrypt');

        self::assertSame(
            [
                'tls' => [
                    'certResolver' => 'letsencrypt',
                ],
            ],
            $generation->getTraefikConfig(),
        );
    }

    public function testGetTraefikConfigEmpty(): void
    {
        self::assertSame([], $this->buildGeneration()->getTraefikConfig());
    }

    public function testAddConfigWithContentWritesFile(): void
    {
        $generation = $this->buildGeneration();
        $generation->addConfig('cfg', ['file' => './configs/cfg'], 'KEY: value');

        self::assertSame(['./configs/cfg' => 'KEY: value'], $generation->getFiles());
    }

    public function testAddSecretWithContentWritesFile(): void
    {
        $generation = $this->buildGeneration();
        $generation->addSecret('sec', ['file' => './secrets/sec'], 'topsecret');

        self::assertSame(['./secrets/sec' => 'topsecret'], $generation->getFiles());
    }

    public function testAddFile(): void
    {
        $generation = $this->buildGeneration();
        $generation
            ->addFile('secrets/a', 'aaa')
            ->addFile('secrets/b', 'bbb');

        self::assertSame(
            [
                'secrets/a' => 'aaa',
                'secrets/b' => 'bbb',
            ],
            $generation->getFiles(),
        );
    }

    public function testGetFilesToCopyReturnsFileToCopyWithMode(): void
    {
        $generation = $this->buildGeneration();
        $generation
            ->addFile('configs/cfg', 'value')
            ->addFile('secrets/sec', 'topsecret');

        $entries = $generation->getFilesToCopy();

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
        $generation = $this->buildGeneration();
        $generation->addTlsCertificate('certs/example.crt', 'certs/example.key');

        $entries = $generation->getCertificatesToCopy();

        self::assertContainsOnlyInstancesOf(FileToCopy::class, $entries);
        self::assertCount(2, $entries);

        self::assertSame('certs/example.crt', $entries[0]->src);
        self::assertSame('example.crt', $entries[0]->dest);
        self::assertNull($entries[0]->mode);

        self::assertSame('certs/example.key', $entries[1]->src);
        self::assertSame('example.key', $entries[1]->dest);
        self::assertNull($entries[1]->mode);
    }

    public function testWireNetworkToTraefikIsDeduplicated(): void
    {
        $generation = $this->buildGeneration();
        $generation
            ->wireNetworkToTraefik('foo-bar_private')
            ->wireNetworkToTraefik('foo-bar_private')
            ->wireNetworkToTraefik('foo-bar_public');

        self::assertSame(
            [
                'foo-bar_private',
                'foo-bar_public',
            ],
            $generation->getNetworksToWire(),
        );
    }
}
