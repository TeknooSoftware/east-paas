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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Behat;

use Teknoo\East\Paas\Compilation\CompiledDeployment;
use function Symfony\Component\Translation\t;

return static function (bool $hnc, string $hncSuffix, string $prefix): CompiledDeployment {
    $cd = new CompiledDeployment(
        version: 1,
        namespace: 'behat-test' . $hncSuffix,
        hierarchicalNamespaces: $hnc,
        prefix: $prefix
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
            tag: 'latest',
            variables: [],
        )
    );

    $cd->addBuildable(
        new CompiledDeployment\Image\EmbeddedVolumeImage(
            name: 'php-run-jobid',
            tag: '7.4',
            originalName: 'registry.teknoo.software/php-run',
            volumes: [
                'app' => new CompiledDeployment\Volume\Volume(
                    name: 'app',
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
            name: 'nginx-jobid',
            tag: 'alpine',
            originalName: 'registry.hub.docker.com/library/nginx',
            volumes: [
                'www' => new CompiledDeployment\Volume\Volume(
                    name: 'www',
                    paths: [
                        'nginx/www',
                    ],
                    localPath: '/volume',
                    mountPath: '/var',
                    isEmbedded: true,
                ),
                'config' => new CompiledDeployment\Volume\Volume(
                    name: 'config',
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
            name: 'extra-jobid',
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
            name: 'other-name-jobid',
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
        name: 'custom-hook:hook-id',
        hook: new HookMock(['foo bar']),
    );

    $cd->addPod(
        name: 'php-pods',
        pod: new CompiledDeployment\Pod(
            name: 'php-pods',
            replicas: 2,
            maxUpgradingPods: 2,
            maxUnavailablePods: 1,
            fsGroup: null,
            requires: [
                'x86_64',
                'avx',
            ],
            containers: [
                new CompiledDeployment\Container(
                    name: 'php-run',
                    image: 'php-run-jobid',
                    version: '7.4',
                    listen: [
                        8080,
                    ],
                    volumes: [
                        'extra' => new CompiledDeployment\Volume\Volume(
                            name: 'extra-jobid',
                            paths: [
                                'extra',
                            ],
                            localPath: '/foo/bar',
                            mountPath: '/opt/extra',
                            isEmbedded: false,
                        ),
                        'data' => new CompiledDeployment\Volume\PersistentVolume(
                            name: 'data',
                            mountPath: '/opt/data',
                            storageIdentifier: 'nfs',
                            storageSize: '3Gi',
                            resetOnDeployment: false,
                        ),
                        'data-replicated' => new CompiledDeployment\Volume\PersistentVolume(
                            name: 'data-replicated',
                            mountPath: '/opt/data-replicated',
                            storageIdentifier: 'replicated-provider',
                            storageSize: '3Gi',
                            resetOnDeployment: false,
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
                        path: null,
                        port: null,
                        isSecure: false,
                        successThreshold: 1,
                        failureThreshold: 1,
                    ),
                ),
            ],
            isStateless: false,
        ),
    );

    $cd->addPod(
        name: 'shell',
        pod: new CompiledDeployment\Pod(
            name: 'shell',
            replicas: 1,
            containers: [
                new CompiledDeployment\Container(
                    name: 'sleep',
                    image: 'registry.hub.docker.com/bash',
                    version: 'alpine',
                    listen: [],
                    volumes: [],
                    variables: [],
                    healthCheck: null,
                ),
            ],
        ),
    );

    $cd->addPod(
        name: 'demo',
        pod: new CompiledDeployment\Pod(
            name: 'demo',
            replicas: 1,
            fsGroup: 1000,
            upgradeStrategy: CompiledDeployment\UpgradeStrategy::Recreate,
            containers: [
                new CompiledDeployment\Container(
                    name: 'nginx',
                    image: 'nginx-jobid',
                    version: 'alpine',
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
                        path: '/status',
                        port: 8080,
                        isSecure: true,
                        successThreshold: 3,
                        failureThreshold: 2,
                    ),
                ),
                new CompiledDeployment\Container(
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
                        path: null,
                        port: 8181,
                        isSecure: false,
                        successThreshold: 1,
                        failureThreshold: 1,
                    ),
                ),
                new CompiledDeployment\Container(
                    name: 'blackfire',
                    image: 'blackfire/blackfire',
                    version: '2',
                    listen: [
                        8307,
                    ],
                    volumes: [],
                    variables: [
                        'BLACKFIRE_SERVER_ID' => 'foo',
                        'BLACKFIRE_SERVER_TOKEN' => 'bar',
                    ],
                    healthCheck: null,
                ),
            ],
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
                'letsencrypt' => '1',
                'annotations' => [
                    'foo2' => 'bar',
                ],
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

    return $cd;
};