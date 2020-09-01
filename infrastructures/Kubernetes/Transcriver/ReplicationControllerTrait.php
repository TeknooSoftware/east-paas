<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriver;

use Maclof\Kubernetes\Models\ReplicationController;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Container\Pod;
use Teknoo\East\Paas\Container\Volume;

trait ReplicationControllerTrait
{
    /**
     * @param array<string, mixed> $specs
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     */
    private static function convertToContainer(array &$specs, Pod $pod, array $images, array $volumes): void
    {
        foreach ($pod as $container) {
            $image = $images[$container->getImage()][$container->getVersion()];
            $spec = [
                'name'  => $container->getName(),
                'image' => $image->getUrl() . ':' . $image->getTag(),
                'imagePullPolicy' => 'Always',
                'ports' => \array_map(
                    fn ($port) => ['containerPort' => $port,],
                    $container->getListen()
                )
            ];

            $envsVars = [];
            foreach ($container->getVariables() as $name => &$value) {
                $envsVars[] = [
                    'name' => $name,
                    'value' => $value
                ];
            }

            if (!empty($envsVars)) {
                $spec['env'] = $envsVars;
            }

            $volumesMount = [];
            foreach ($container->getVolumes() as $volume) {
                $volumesMount[] = [
                    'name' => $volumes[$volume]->getName(),
                    'mountPath' => $volumes[$volume]->getTarget(),
                    'readOnly' => true,
                ];
            }

            if (!empty($volumesMount)) {
                $spec['volumeMounts'] = $volumesMount;
            }

            $specs['spec']['template']['spec']['containers'][] = $spec;
        }
    }

    /**
     * @param array<string, mixed> $specs
     * @param array<string, Volume>|Volume[] $volumes
     */
    private static function convertToVolumes(array &$specs, array $volumes): void
    {
        foreach ($volumes as $volume) {
            $specs['spec']['template']['spec']['containers'][] = [
                'name' => $volume->getName(),
                'image' => $volume->getUrl(),
                'imagePullPolicy' => 'Always',
                'volumeMounts' => [
                    [
                        'name' => $volume->getName(),
                        'mountPath' => $volume->getMountPath(),
                        'readOnly' => false,
                    ]
                ]
            ];

            $specs['spec']['template']['spec']['volumes'][] = [
                'name' => $volume->getName(),
                'emptyDir' => []
            ];
        }
    }

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     */
    private static function convertToReplicationController(
        Pod $pod,
        array $images,
        array $volumes
    ): ReplicationController {
        $specs = [
            'metadata' => [
                'name' => $pod->getName() . '-deployment',
                'labels' => [
                    'name' => $pod->getName(),
                ],
            ],
            'spec' => [
                'replicas' => $pod->getReplicas(),
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'name' => $pod->getName(),
                        ],
                    ],
                    'spec' => [
                        'containers' => []
                    ],
                ],
            ],
        ];

        self::convertToVolumes($specs, $volumes);
        self::convertToContainer($specs, $pod, $images, $volumes);

        return new ReplicationController($specs);
    }

    private function foreachReplicationController(CompiledDeployment $deployment, callable $callback): void
    {
        $deployment->foreachPod(static function (Pod $pod, array $images, array $volumes) use ($callback) {
            $callback(static::convertToReplicationController($pod, $images, $volumes));
        });
    }
}
