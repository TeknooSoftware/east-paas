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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;
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

    public function testGetNetworkName(): void
    {
        self::assertSame('foo-bar-private', $this->buildAccumulator()->getNetworkName());
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
        self::assertInstanceOf(
            AccumulatorInterface::class,
            $accumulator->addTraefikServersTransport('t', ['insecureSkipVerify' => true]),
        );
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->setCertResolver('le'));
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->addFile('secrets/sec', 'value'));
    }

    public function testGetComposeFileShape(): void
    {
        $accumulator = $this->buildAccumulator();

        $accumulator
            ->addService('php', [
                'image' => 'php:8.4',
                'networks' => ['foo-bar-private'],
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
                        'networks' => ['foo-bar-private'],
                        'expose' => [9000],
                    ],
                ],
                'networks' => [
                    'foo-bar-private' => [
                        'name' => 'foo-bar-private',
                        'driver' => 'bridge',
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

    public function testGetComposeFileWithInternalNetwork(): void
    {
        $accumulator = new Accumulator('foo-bar', 'private', 'bridge', true);
        $accumulator->addService('php', ['image' => 'php:8.4']);

        self::assertSame(
            [
                'foo-bar-private' => [
                    'name' => 'foo-bar-private',
                    'driver' => 'bridge',
                    'internal' => true,
                ],
            ],
            $accumulator->getComposeFile()['networks'],
        );
    }

    public function testAddNetworkAliasOnListNetworks(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('php', ['image' => 'php', 'networks' => ['foo-bar-private']])
            ->addNetworkAlias('php', 'php-service')
            ->addNetworkAlias('php', 'php-service')
            ->addNetworkAlias('php', 'php')
            ->addNetworkAlias('unknown', 'foo');

        self::assertSame(
            ['foo-bar-private' => ['aliases' => ['php-service']]],
            $accumulator->getComposeFile()['services']['php']['networks'],
        );
    }

    public function testAddNetworkAliasKeepsShortFormWhenAliasIsTheServiceName(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('php', ['image' => 'php', 'networks' => ['foo-bar-private']])
            ->addService('sidecar', ['image' => 'php', 'network_mode' => 'service:php'])
            ->addNetworkAlias('php', 'php')
            ->addNetworkAlias('sidecar', 'other');

        $services = $accumulator->getComposeFile()['services'];
        self::assertSame(['foo-bar-private'], $services['php']['networks']);
        self::assertArrayNotHasKey('networks', $services['sidecar']);
    }

    public function testWarnings(): void
    {
        $accumulator = $this->buildAccumulator();
        self::assertSame([], $accumulator->getWarnings());
        self::assertInstanceOf(AccumulatorInterface::class, $accumulator->addWarning('foo'));
        self::assertSame(['foo'], $accumulator->getWarnings());
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
                            'certFile' => '/etc/traefik/certs/example.crt',
                            'keyFile' => '/etc/traefik/certs/example.key',
                        ],
                    ],
                ],
            ],
            $accumulator->getTraefikConfig(),
        );
    }

    public function testGetTraefikConfigWithServersTransportAndCustomCertsDir(): void
    {
        $accumulator = new Accumulator('foo-bar', 'private', 'bridge', false, '/certs');
        $accumulator
            ->addTraefikServersTransport('app-transport', ['insecureSkipVerify' => true])
            ->addTlsCertificate('certs/example.crt', 'certs/example.key');

        self::assertSame(
            [
                'http' => [
                    'serversTransports' => [
                        'app-transport' => ['insecureSkipVerify' => true],
                    ],
                ],
                'tls' => [
                    'certificates' => [
                        [
                            'certFile' => '/certs/example.crt',
                            'keyFile' => '/certs/example.key',
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

    /**
     * @param list<string> $services
     * @param array<int, int> $okCodes
     * @return array<string, mixed>
     */
    private static function jobRun(
        string $service,
        array $services,
        int $run,
        int $completions,
        int $timeout,
        array $okCodes,
    ): array {
        return [
            'service' => $service,
            'services' => $services,
            'run' => $run,
            'completions' => $completions,
            'timeout' => $timeout,
            'ok_codes' => $okCodes,
        ];
    }

    public function testGetJobsToRun(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('php', ['image' => 'php'])
            ->addService('migrate', ['image' => 'php', 'profiles' => ['jobs']])
            ->addService('seed', [
                'image' => 'php',
                'profiles' => ['jobs'],
                'x-paas-job' => [
                    'parallel' => true,
                    'completions' => 2,
                    'time_limit' => 30,
                    'success_exit_codes' => [0, 3, 3],
                ],
            ]);

        self::assertSame(
            [
                self::jobRun('migrate', ['migrate'], 1, 1, 0, [0]),
                self::jobRun('seed', ['seed'], 1, 2, 30, [0, 3]),
                self::jobRun('seed', ['seed'], 2, 2, 30, [0, 3]),
            ],
            $accumulator->getJobsToRun(),
        );
    }

    public function testGetJobsToRunGroupsTheSidecarsOfAPodWithItsAnchor(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('php', ['image' => 'php'])
            //Declared before its anchor, listed after it
            ->addService('job-dump-proxy', [
                'image' => 'proxy',
                'profiles' => ['jobs'],
                'network_mode' => 'service:job-dump',
                'x-paas-job' => ['completions' => 5],
            ])
            ->addService('job-dump', [
                'image' => 'alpine',
                'profiles' => ['jobs'],
                'x-paas-job' => ['completions' => 2, 'time_limit' => 30],
            ])
            ->addService('job-dump-agent', [
                'image' => 'agent',
                'profiles' => ['jobs'],
                'network_mode' => 'service:job-dump',
            ])
            //Outside the profile or itself: a run of its own
            ->addService('job-orphan', ['image' => 'alpine', 'profiles' => ['jobs'], 'network_mode' => 'service:php'])
            ->addService('job-loop', [
                'image' => 'alpine',
                'profiles' => ['jobs'],
                'network_mode' => 'service:job-loop',
            ])
            ->addService('job-host', ['image' => 'alpine', 'profiles' => ['jobs'], 'network_mode' => 'host'])
            ->addService('job-backup', ['image' => 'alpine', 'profiles' => ['scheduled']])
            ->addService('job-other', [
                'image' => 'alpine',
                'profiles' => ['jobs'],
                'network_mode' => 'service:job-backup',
            ]);

        $pod = ['job-dump', 'job-dump-proxy', 'job-dump-agent'];
        self::assertSame(
            [
                self::jobRun('job-dump', $pod, 1, 2, 30, [0]),
                self::jobRun('job-dump', $pod, 2, 2, 30, [0]),
                self::jobRun('job-orphan', ['job-orphan'], 1, 1, 0, [0]),
                self::jobRun('job-loop', ['job-loop'], 1, 1, 0, [0]),
                self::jobRun('job-host', ['job-host'], 1, 1, 0, [0]),
                self::jobRun('job-other', ['job-other'], 1, 1, 0, [0]),
            ],
            $accumulator->getJobsToRun(),
        );
    }

    public function testAddNetworkAliasIgnoresANonArrayNetworksKey(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('php', ['image' => 'php', 'networks' => 'foo-bar-private'])
            ->addNetworkAlias('php', 'php-service');

        self::assertSame('foo-bar-private', $accumulator->getComposeFile()['services']['php']['networks']);
    }

    public function testGetJobsToRunIgnoresANonArrayJobMeta(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addService('job', ['image' => 'php', 'profiles' => ['jobs'], 'x-paas-job' => 'nope']);

        self::assertSame(
            [self::jobRun('job', ['job'], 1, 1, 0, [0])],
            $accumulator->getJobsToRun(),
        );
    }

    public function testGetScheduledJobs(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('php', ['image' => 'php'])
            ->addService('migrate', [
                'image' => 'php',
                'profiles' => ['jobs'],
                'x-paas-schedule' => ['schedule' => '0 * * * *'],
            ])
            ->addService('job-backup-dump', [
                'image' => 'alpine',
                'profiles' => ['scheduled'],
                'restart' => 'no',
                'x-paas-job' => ['parallel' => false, 'completions' => 2, 'time_limit' => 600],
                'x-paas-schedule' => [
                    'schedule' => '17 3 * * *',
                    'time_zone' => 'Europe/Paris',
                    'concurrency' => 'forbid',
                    'suspend' => true,
                    'starting_deadline' => 300,
                ],
            ])
            ->addService('job-report-report', [
                'image' => 'alpine',
                'profiles' => ['scheduled'],
                'x-paas-schedule' => [
                    'schedule' => '0 */3 * * *',
                    'concurrency' => 'replace',
                    'suspend' => false,
                ],
            ]);

        self::assertSame(
            [
                [
                    'name' => 'east-paas:foo-bar:job-backup-dump',
                    'service' => 'job-backup-dump',
                    'services' => ['job-backup-dump'],
                    'disabled' => true,
                    'job' => "PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh 'job-backup-dump' "
                        . "'17 3 * * *' >>'jobs/job-backup-dump/events.log' 2>&1",
                ],
                [
                    'name' => 'east-paas:foo-bar:job-report-report',
                    'service' => 'job-report-report',
                    'services' => ['job-report-report'],
                    'disabled' => false,
                    'job' => "PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh 'job-report-report' "
                        . "'0 */3 * * *' >>'jobs/job-report-report/events.log' 2>&1",
                ],
            ],
            $accumulator->getScheduledJobs(),
        );

        //The scheduled services are not run during the deployment
        self::assertSame(
            [self::jobRun('migrate', ['migrate'], 1, 1, 0, [0])],
            $accumulator->getJobsToRun(),
        );
    }

    public function testGetScheduledJobsGroupsTheSidecarsOfAPodWithItsAnchor(): void
    {
        $schedule = [
            'schedule' => '0 3 * * *',
            'concurrency' => 'forbid',
        ];

        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('job-backup-dump', [
                'image' => 'alpine',
                'profiles' => ['scheduled'],
                'x-paas-job' => ['completions' => 2, 'time_limit' => 600],
                'x-paas-schedule' => $schedule,
            ])
            //Its own meta is ignored, the pod runs with the entry of its anchor
            ->addService('job-backup-dump-proxy', [
                'image' => 'proxy',
                'profiles' => ['scheduled'],
                'network_mode' => 'service:job-backup-dump',
                'x-paas-schedule' => ['schedule' => '0 4 * * *', 'suspend' => true] + $schedule,
            ])
            ->addService('job-migrate', ['image' => 'php', 'profiles' => ['jobs']])
            //Outside the profile: an entry of its own
            ->addService('job-orphan', [
                'image' => 'alpine',
                'profiles' => ['scheduled'],
                'network_mode' => 'service:job-migrate',
                'x-paas-schedule' => $schedule,
            ]);

        $entries = $accumulator->getScheduledJobs();

        self::assertSame(
            ['east-paas:foo-bar:job-backup-dump', 'east-paas:foo-bar:job-orphan'],
            array_column($entries, 'name'),
        );
        self::assertSame('job-backup-dump', $entries[0]['service']);
        self::assertSame(['job-backup-dump', 'job-backup-dump-proxy'], $entries[0]['services']);
        self::assertFalse($entries[0]['disabled']);
        self::assertSame(
            "PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh 'job-backup-dump' '0 3 * * *' "
            . ">>'jobs/job-backup-dump/events.log' 2>&1",
            $entries[0]['job'],
        );
        self::assertSame(['job-orphan'], $entries[1]['services']);

        //The sidecar of a pod of the profile `jobs` is not a run either
        self::assertSame([self::jobRun('job-migrate', ['job-migrate'], 1, 1, 0, [0])], $accumulator->getJobsToRun());
    }

    public function testGetScheduledJobsQuotesEachArgumentOnASingleLine(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addService("it's", [
            'image' => 'php',
            'profiles' => ['other', 'scheduled'],
            'x-paas-schedule' => [
                'schedule' => " 0  3\n* * *\t",
                'suspend' => 'yes',
            ],
        ]);

        $entries = $accumulator->getScheduledJobs();

        self::assertCount(1, $entries);
        self::assertFalse($entries[0]['disabled']);
        self::assertSame(
            "PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh 'it'\\''s' '0 3 * * *' "
            . ">>'jobs/it'\\''s/events.log' 2>&1",
            $entries[0]['job'],
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideCrontabBreakers(): iterable
    {
        yield 'percent in the schedule' => ['job', '0 3 * * * %'];
        yield 'percent in the anchor' => ['job-%x', '0 3 * * *'];
        yield 'newline in the anchor' => ["job\n* * * * * id", '0 3 * * *'];
        yield 'carriage return in the anchor' => ["job\r", '0 3 * * *'];
    }

    #[DataProvider('provideCrontabBreakers')]
    public function testGetScheduledJobsRefusesAnEntryWithAPercentOrALineBreak(string $name, string $schedule): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addService(
            $name,
            [
                'image' => 'php',
                'profiles' => ['scheduled'],
                'x-paas-schedule' => ['schedule' => $schedule, 'time_zone' => 'Europe/%x'],
            ],
        );

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            "The scheduled job service `$name` can not be installed in a crontab: its entry would contain a `%` or a "
            . 'line break',
        );

        $accumulator->getScheduledJobs();
    }

    public function testGetScheduledJobsAcceptsAPercentOutsideTheEntry(): void
    {
        //The time zone is read by the runner from the configuration of the job, not from the entry
        $accumulator = $this->buildAccumulator();
        $accumulator->addService(
            'job',
            [
                'image' => 'php',
                'profiles' => ['scheduled'],
                'x-paas-schedule' => ['schedule' => '0 3 * * *', 'time_zone' => "Europe/%x\n"],
            ],
        );

        self::assertCount(1, $accumulator->getScheduledJobs());
    }

    public function testGetScheduledJobsIgnoresANonStringSchedule(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator->addService('job', [
            'image' => 'php',
            'profiles' => ['scheduled'],
            'x-paas-schedule' => [
                'schedule' => ['0 3 * * *'],
            ],
        ]);

        self::assertSame(
            "PATH=/usr/local/bin:/usr/bin:/bin sh jobs/east-paas-cron.sh 'job' '' >>'jobs/job/events.log' 2>&1",
            $accumulator->getScheduledJobs()[0]['job'],
        );
    }

    public function testGetScheduledJobsIgnoresServicesWithoutAValidScheduleMeta(): void
    {
        $accumulator = $this->buildAccumulator();
        $accumulator
            ->addService('without-meta', ['image' => 'php', 'profiles' => ['scheduled']])
            ->addService('non-array-meta', ['image' => 'php', 'profiles' => ['scheduled'], 'x-paas-schedule' => 'nope'])
            ->addService('non-array-profiles', [
                'image' => 'php',
                'profiles' => 'scheduled',
                'x-paas-schedule' => ['schedule' => '0 3 * * *'],
            ]);

        self::assertSame([], $accumulator->getScheduledJobs());
    }
}
