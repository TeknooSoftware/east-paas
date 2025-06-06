<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Behat;

use Teknoo\East\Paas\Compilation\CompiledDeployment;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\CompletionMode;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\SuccessCondition;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod\RestartPolicy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\Reference;

return static function (
    string $prefix,
    string $withQuota,
    string $withDefaults,
    string $projectName,
    bool $withJob,
    bool $withCondition,
): CompiledDeployment {
    $cd = new CompiledDeployment(
        version: $withJob || $withCondition ? 1.1 : 1,
        prefix: $prefix,
        projectName: $projectName,
    );

    if (!empty($prefix)) {
        $prefix .= '-';
    }

    $cd->addSecret(
        name: 'map-vault',
        secret: new CompiledDeployment\Secret(
            name: 'map-vault',
            provider: 'map',
            options: [
                'key1' => 'value1',
                'key2' => 'foo',
            ],
            type: 'default',
        ),
    );

    $cd->addSecret(
        name: 'map-vault2',
        secret: new CompiledDeployment\Secret(
            name: 'map-vault2',
            provider: 'map',
            options: [
                'hello' => $prefix . 'world',
            ],
            type: 'default',
        ),
    );

    $cd->addSecret(
        name: 'volume-vault',
        secret: new CompiledDeployment\Secret(
            name: 'volume-vault',
            provider: 'map',
            options: [
                'foo' => 'bar',
                'bar' => 'foo',
            ],
            type: 'foo',
        ),
    );

    $cd->addMap(
        name: 'map1',
        map: new CompiledDeployment\Map(
            name: 'map1',
            options: [
                'key1' => 'value1',
                'key2' => 'foo',
            ],
        ),
    );

    $cd->addMap(
        name: 'map2',
        map: new CompiledDeployment\Map(
            name: 'map2',
            options: [
                'foo' => 'bar',
                'bar' => $prefix . 'foo',
            ],
        ),
    );

    $cd->addBuildable(
        new CompiledDeployment\Image\Image(
            name: 'foo',
            path: '/foo/images/foo',
            library: false,
            tag: 'prod',
            variables: [],
        )
    );

    if ($withJob) {
        $cd->addBuildable(
            new CompiledDeployment\Image\EmbeddedVolumeImage(
                name: 'php-run',
                tag: '7.4-b424d-43879-43879-prod',
                originalName: 'registry.teknoo.software/php-run',
                originalTag: '7.4',
                volumes: [
                    'app' => new CompiledDeployment\Volume\Volume(
                        name: 'app-b424d-43879-43879',
                        paths: [
                            'src',
                            'vendor',
                            'composer.json',
                            'composer.lock',
                            'composer.phar',
                        ],
                        localPath: '/volume',
                        mountPath: '/opt/app',
                        writables: [
                            'var/*',
                        ],
                        isEmbedded: true,
                    ),
                ],
            ),
        );
    }

    $cd->addBuildable(
        new CompiledDeployment\Image\EmbeddedVolumeImage(
            name: 'php-run',
            tag: '7.4-09597-1e225-prod',
            originalName: 'registry.teknoo.software/php-run',
            originalTag: '7.4',
            volumes: [
                'app' => new CompiledDeployment\Volume\Volume(
                    name: 'app-09597-1e225',
                    paths: [
                        'src',
                        'var',
                        'vendor',
                        'composer.json',
                        'composer.lock',
                        'composer.phar',
                    ],
                    localPath: '/volume',
                    mountPath: '/opt/app',
                    writables: [
                        'var/*',
                    ],
                    isEmbedded: true,
                ),
            ],
        ),
    );

    $cd->addBuildable(
        new CompiledDeployment\Image\EmbeddedVolumeImage(
            name: 'nginx',
            tag: 'alpine-2a975-5be1e-prod',
            originalName: 'registry.hub.docker.com/library/nginx',
            originalTag: 'alpine',
            volumes: [
                'www' => new CompiledDeployment\Volume\Volume(
                    name: 'www-2a975-5be1e',
                    paths: [
                        'nginx/www',
                    ],
                    localPath: '/volume',
                    mountPath: '/var',
                    isEmbedded: true,
                ),
                'config' => new CompiledDeployment\Volume\Volume(
                    name: 'config-2a975-5be1e',
                    paths: [
                        'nginx/conf.d/default.conf',
                    ],
                    localPath: '/volume',
                    mountPath: '/etc/nginx/conf.d/',
                    isEmbedded: true,
                ),
            ],
        ),
    );

    $cd->addVolume(
        name: 'extra',
        volume: new CompiledDeployment\Volume\Volume(
            name: 'extra-foobarproject',
            paths: [
                'extra',
            ],
            localPath: '/foo/bar',
            mountPath: '/mnt',
            isEmbedded: false,
        ),
    );

    $cd->addVolume(
        name: 'other-name',
        volume: new CompiledDeployment\Volume\Volume(
            name: 'other-name-foobarproject',
            paths: [
                'vendor',
            ],
            localPath: '/volume',
            mountPath: '/mnt',
            isEmbedded: false,
        ),
    );

    $cd->addHook(
        name: 'composer-build:composer',
        hook: new HookMock(
            [
                'action' => 'install',
                'arguments' => [
                    'no-dev',
                    'optimize-autoloader',
                    'classmap-authoritative',
                ],
            ],
        ),
    );

    $cd->addHook(
        name: 'custom-hook:hook-id-foo',
        hook: new HookMock(['foo bar']),
    );

    $automaticResources = [
        (new CompiledDeployment\AutomaticResource('cpu'))->setLimit('200m', '1.600'),
        (new CompiledDeployment\AutomaticResource('memory'))->setLimit('20.480Mi', '163.840Mi'),
    ];

    $phpRunResources = match ($withQuota) {
        'automatic' => $automaticResources,
        'partial' => [
            (new CompiledDeployment\AutomaticResource('cpu'))->setLimit('68m', '561m'),
            (new CompiledDeployment\AutomaticResource('memory'))->setLimit('9.600Mi', '80Mi'),
        ],
        'full' => [
            new CompiledDeployment\Resource('cpu', '200m', '500m'),
            new CompiledDeployment\Resource('memory', '64Mi', '96Mi'),
        ],
        default => []
    };

    $shellResources = match ($withQuota) {
        'automatic' => $automaticResources,
        'partial' => [
            new CompiledDeployment\Resource('cpu', '100m', '100m'),
            (new CompiledDeployment\AutomaticResource('memory'))->setLimit('9.600Mi', '80Mi'),
        ],
        'full' => [
            new CompiledDeployment\Resource('cpu', '100m', '100m'),
            new CompiledDeployment\Resource('memory', '32Mi', '32Mi'),
        ],
        default => []
    };

    $nginxResources = match ($withQuota) {
        'automatic' => $automaticResources,
        'partial' => [
            (new CompiledDeployment\AutomaticResource('cpu'))->setLimit('68m', '561m'),
            (new CompiledDeployment\AutomaticResource('memory'))->setLimit('9.600Mi', '80Mi'),
        ],
        'full' => [
            new CompiledDeployment\Resource('cpu', '200m', '200m'),
            new CompiledDeployment\Resource('memory', '64Mi', '64Mi'),
        ],
        default => []
    };

    $wafResources = match ($withQuota) {
        'automatic' => $automaticResources,
        'partial', 'full' => [
            new CompiledDeployment\Resource('cpu', '100m', '100m'),
            new CompiledDeployment\Resource('memory', '64Mi', '64Mi'),
        ],
        default => []
    };

    $blackfireResources = match ($withQuota) {
        'automatic' => $automaticResources,
        'partial', 'full' => [
            new CompiledDeployment\Resource('cpu', '100m', '100m'),
            new CompiledDeployment\Resource('memory', '128Mi', '128Mi'),
        ],
        default => []
    };

    $cd->addPod(
        name: 'php-pods',
        pod: new Pod(
            name: 'php-pods',
            replicas: 2,
            containers: [
                new Container(
                    name: 'php-run',
                    image: 'php-run',
                    version: '7.4-09597-1e225-prod',
                    listen: [
                        8080,
                    ],
                    volumes: [
                        'extra' => new CompiledDeployment\Volume\Volume(
                            name: 'extra-foobarproject',
                            paths: [
                                'extra',
                            ],
                            localPath: '/foo/bar',
                            mountPath: '/opt/extra',
                            isEmbedded: false,
                        ),
                        'data' => new CompiledDeployment\Volume\PersistentVolume(
                            name: 'data-09597-1e225',
                            mountPath: '/opt/data',
                            storageIdentifier: new Reference('storage-provider'),
                            storageSize: '3Gi',
                            resetOnDeployment: false,
                        ),
                        'data-replicated' => new CompiledDeployment\Volume\PersistentVolume(
                            name: 'data-replicated',
                            mountPath: '/opt/data-replicated',
                            storageIdentifier: 'replicated-provider',
                            storageSize: '3Gi',
                            resetOnDeployment: false,
                            allowWriteMany: true
                        ),
                        'map' => new CompiledDeployment\Volume\MapVolume(
                            name: 'map',
                            mountPath: '/map',
                            mapIdentifier: 'map2',
                        ),
                        'vault' => new CompiledDeployment\Volume\SecretVolume(
                            name: 'vault',
                            mountPath: '/vault',
                            secretIdentifier: 'volume-vault',
                        ),
                    ],
                    variables: [
                        'SERVER_SCRIPT' => '/opt/app/src/server.php',
                        'import-secrets-0' => new CompiledDeployment\SecretReference(
                            name: 'map-vault2',
                            key: null,
                            importAll: true,
                        ),
                        'KEY1' => new CompiledDeployment\SecretReference(
                            name: 'map-vault',
                            key: 'key1',
                            importAll: false,
                        ),
                        'KEY2' => new CompiledDeployment\SecretReference(
                            name: 'map-vault',
                            key: 'key2',
                            importAll: false,
                        ),
                        'import-maps-0' => new CompiledDeployment\MapReference(
                            name: 'map2',
                            key: null,
                            importAll: true,
                        ),
                        'KEY0' => new CompiledDeployment\MapReference(
                            name: 'map1',
                            key: 'key0',
                            importAll: false,
                        ),
                    ],
                    healthCheck: new CompiledDeployment\HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: CompiledDeployment\HealthCheckType::Command,
                        command: ['ps', 'aux', 'php'],
                        port: null,
                        path: null,
                        isSecure: false,
                        successThreshold: 1,
                        failureThreshold: 1,
                    ),
                    resources: new CompiledDeployment\ResourceSet($phpRunResources),
                ),
            ],
            ociRegistryConfigName: new Reference('oci-registry-config-name'),
            maxUpgradingPods: 2,
            maxUnavailablePods: 1,
            fsGroup: null,
            requires: [
                'x86_64',
                'avx',
            ],
            isStateless: false,
        ),
    );

    $cd->addPod(
        name: 'shell',
        pod: new Pod(
            name: 'shell',
            replicas: 1,
            containers: [
                new Container(
                    name: 'sleep',
                    image: 'registry.hub.docker.com/bash',
                    version: 'alpine',
                    listen: [],
                    volumes: [],
                    variables: [],
                    healthCheck: null,
                    resources: new CompiledDeployment\ResourceSet($shellResources),
                ),
            ],
            ociRegistryConfigName: new Reference('oci-registry-config-name'),
        ),
    );

    $cd->addPod(
        name: 'demo',
        pod: new Pod(
            name: 'demo',
            replicas: 1,
            containers: [
                new Container(
                    name: 'nginx',
                    image: 'nginx',
                    version: 'alpine-2a975-5be1e-prod',
                    listen: [
                        8080,
                        8181,
                    ],
                    volumes: [],
                    variables: [],
                    healthCheck: new CompiledDeployment\HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: CompiledDeployment\HealthCheckType::Http,
                        command: null,
                        port: 8080,
                        path: '/status',
                        isSecure: true,
                        successThreshold: 3,
                        failureThreshold: 2,
                    ),
                    resources: new CompiledDeployment\ResourceSet($nginxResources),
                ),
                new Container(
                    name: 'waf',
                    image: 'registry.hub.docker.com/library/waf',
                    version: 'alpine',
                    listen: [
                        8181,
                    ],
                    volumes: [],
                    variables: [],
                    healthCheck: new CompiledDeployment\HealthCheck(
                        initialDelay: 10,
                        period: 30,
                        type: CompiledDeployment\HealthCheckType::Tcp,
                        command: null,
                        port: 8181,
                        path: null,
                        isSecure: false,
                        successThreshold: 1,
                        failureThreshold: 1,
                    ),
                    resources: new CompiledDeployment\ResourceSet($wafResources),
                ),
                new Container(
                    name: 'blackfire',
                    image: 'blackfire/blackfire',
                    version: '2-prod',
                    listen: [
                        8307,
                    ],
                    volumes: [],
                    variables: [
                        'BLACKFIRE_SERVER_ID' => 'foo',
                        'BLACKFIRE_SERVER_TOKEN' => 'bar',
                    ],
                    healthCheck: null,
                    resources: new CompiledDeployment\ResourceSet($blackfireResources),
                ),
            ],
            ociRegistryConfigName: new Reference('oci-registry-config-name'),
            upgradeStrategy: CompiledDeployment\UpgradeStrategy::Recreate,
            fsGroup: 1000,
        ),
    );

    $cd->addService(
        name: 'php-service',
        service: new CompiledDeployment\Expose\Service(
            name: 'php-service',
            podName: 'php-pods',
            ports: [
                9876 => 8080,
            ],
            protocol: CompiledDeployment\Expose\Transport::Tcp,
            internal: false,
        )
    );

    $cd->addService(
        name: 'demo',
        service: new CompiledDeployment\Expose\Service(
            name: 'demo',
            podName: 'demo',
            ports: [
                8080 => 8080,
                8181 => 8181,
            ],
            protocol: CompiledDeployment\Expose\Transport::Tcp,
            internal: true,
        )
    );

    $cd->addIngress(
        name: 'demo',
        ingress: new CompiledDeployment\Expose\Ingress(
            name: 'demo',
            host: 'demo-paas.teknoo.software',
            provider: null,
            defaultServiceName: 'demo',
            defaultServicePort: 8080,
            paths: [
                new CompiledDeployment\Expose\IngressPath(
                    path: '/php',
                    serviceName: 'php-service',
                    servicePort: 9876,
                ),
            ],
            tlsSecret: 'demo-vault',
            httpsBackend: false,
            meta: [
                'letsencrypt' => true,
                'annotations' => [
                    'foo2' => 'bar',
                ],
            ],
            aliases: [
                'demo-paas.teknoo.software',
                'alias1.demo-paas.teknoo.software',
                'alias2.demo-paas.teknoo.software',
            ],
        )
    );

    $cd->addIngress(
        name: 'demo-secure',
        ingress: new CompiledDeployment\Expose\Ingress(
            name: 'demo-secure',
            host: 'demo-secure.teknoo.software',
            provider: null,
            defaultServiceName: 'demo',
            defaultServicePort: 8181,
            paths: [],
            tlsSecret: 'demo-vault',
            httpsBackend: true,
        )
    );

    $cd->setDefaultBags(
        match ($withDefaults) {
            'system' => new DefaultsBag(
                values: [
                    'storage-provider' => 'system-defaults-storage-identifiers',
                    'storage-size' => '987Gi',
                    'oci-registry-config-name' => 'system-oci-registry-behat',
                ]
            ),
            'generic' => new DefaultsBag(
                values: [
                    'storage-provider' => 'user-default-behat-provider',
                    'storage-size' => '123Gi',
                    'oci-registry-config-name' => 'oci-registry-behat',
                ]
            ),
            'cluster' => (static function () {
                $parent = new DefaultsBag(
                    values: [
                        'storage-provider' => 'user-default-behat-provider',
                        'storage-size' => '123Gi',
                        'oci-registry-config-name' => 'oci-registry-behat',
                    ]
                );
                $parent->forCluster('behat-cluster')
                    ->set('storage-provider', 'cluster-default-behat-provider');

                return $parent;
            })(),
            'job-generic' => new DefaultsBag(
                values: [
                    'storage-provider' => 'job-default-behat-provider',
                    'storage-size' => '45Gi',
                    'oci-registry-config-name' => 'oci-registry-behat-job',
                ]
            ),
            'job-cluster' => (static function () {
                $parent = new DefaultsBag(
                    values: [
                        'storage-provider' => 'job-default-behat-provider',
                        'storage-size' => '45Gi',
                        'oci-registry-config-name' => 'oci-registry-behat-job',
                    ]
                );
                $parent->forCluster('behat-cluster')
                    ->set('storage-provider', 'job-cluster-default-behat-provider');

                return $parent;
            })(),
            default => new DefaultsBag(
                values: [
                    'storage-provider' => 'nfs',
                    'oci-registry-config-name' => null,
                ]
            ),
        },
    );

    if ($withJob) {
        $cd->addJob(
            'job-init',
            new Job(
                name: 'job-init',
                completion: CompletionMode::Indexed,
                completionsCount: 3,
                timeLimit: 10,
                shelfLife: 20,
                isParallel: true,
                pods: [
                    'init-var' => new Pod(
                        name: 'init-var',
                        replicas: 1,
                        ociRegistryConfigName: new Reference('oci-registry-config-name'),
                        containers: [
                            new Container(
                                name: 'init-var',
                                image: 'registry.hub.docker.com/bash',
                                version: 'alpine',
                            ),
                        ],
                    ),
                    'update' => new Pod(
                        name: 'update',
                        replicas: 1,
                        ociRegistryConfigName: new Reference('oci-registry-config-name'),
                        containers: [
                            new Container(
                                name: 'update',
                                image: 'registry.hub.docker.com/bash',
                                version: 'alpine',
                            ),
                        ],
                    ),
                ],
            ),
        );

        $cd->addJob(
            'job-translation',
            new Job(
                name: 'job-translation',
                successCondition: new SuccessCondition(
                    successExitCode: [0, 5],
                    failureExistCode: [1],
                    containerName: 'php-translation',
                ),
                shelfLife: null,
                pods: [
                    'php-translation' => new Pod(
                        name: 'php-translation',
                        replicas: 1,
                        isStateless: false,
                        ociRegistryConfigName: new Reference('oci-registry-config-name'),
                        containers: [
                            new Container(
                                name: 'php-translation',
                                image: 'php-run',
                                version: '7.4-b424d-43879-43879-prod',
                                volumes: [
                                    'extra' => new CompiledDeployment\Volume\Volume(
                                        name: 'extra-foobarproject',
                                        paths: [
                                            'extra',
                                        ],
                                        localPath: '/foo/bar',
                                        mountPath: '/opt/extra',
                                        isEmbedded: false,
                                    ),
                                    'data' => new CompiledDeployment\Volume\PersistentVolume(
                                        name: 'data-b424d-43879-43879',
                                        mountPath: '/opt/data',
                                        storageIdentifier: new Reference('storage-provider'),
                                        storageSize: '3Gi',
                                        resetOnDeployment: false,
                                    ),
                                    'data-replicated' => new CompiledDeployment\Volume\PersistentVolume(
                                        name: 'data-replicated',
                                        mountPath: '/opt/data-replicated',
                                        storageIdentifier: 'replicated-provider',
                                        storageSize: '3Gi',
                                        resetOnDeployment: false,
                                        allowWriteMany: true
                                    ),
                                    'map' => new CompiledDeployment\Volume\MapVolume(
                                        name: 'map',
                                        mountPath: '/map',
                                        mapIdentifier: 'map2',
                                    ),
                                    'vault' => new CompiledDeployment\Volume\SecretVolume(
                                        name: 'vault',
                                        mountPath: '/vault',
                                        secretIdentifier: 'volume-vault',
                                    ),
                                ],
                                variables: [
                                    'SERVER_SCRIPT' => '/opt/app/src/server.php',
                                    'import-maps-0' => new CompiledDeployment\MapReference(
                                        name: 'map2',
                                        key: null,
                                        importAll: true,
                                    ),
                                    'KEY0' => new CompiledDeployment\MapReference(
                                        name: 'map1',
                                        key: 'key0',
                                        importAll: false,
                                    ),
                                ],
                                healthCheck: null,
                                resources: new CompiledDeployment\ResourceSet($phpRunResources),
                            ),
                        ],
                    )
                ],
            ),
        );

        $cd->addJob(
            'job-backup',
            new Job(
                name: 'job-backup',
                planning: Planning::Scheduled,
                planningSchedule: '0 0 /3 * * *',
                shelfLife: 60 * 60,
                pods: [
                    'backup' => new Pod(
                        name: 'backup',
                        replicas: 1,
                        ociRegistryConfigName: new Reference('oci-registry-config-name'),
                        restartPolicy: RestartPolicy::OnFailure,
                        containers: [
                            new Container(
                                name: 'backup',
                                image: 'registry.hub.docker.com/backup',
                                version: 'alpine',
                            )
                        ]
                    ),
                ],
            ),
        );
    }

    return $cd;
};