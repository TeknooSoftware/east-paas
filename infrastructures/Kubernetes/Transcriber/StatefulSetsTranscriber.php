<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\StatefulSet;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Throwable;

use function substr;

/**
 * "Stateful Sets transcriber" to translate CompiledDeployment's pods and containers to Kubernetes ReplicationsSet
 * manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class StatefulSetsTranscriber implements DeploymentInterface
{
    use CommonTrait;
    use PodsTranscriberTrait;

    private const NAME_SUFFIX = '-sfset';
    private const POD_SUFFIX = '-pod';
    private const VOLUME_SUFFIX = '-volume';
    private const SECRET_SUFFIX = '-secret';
    private const MAP_SUFFIX = '-map';


    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     * @return array<string, mixed>
     */
    protected static function writeSpec(
        string $name,
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace,
        int $version,
        callable $prefixer,
        string $requireLabel,
    ): array {
        return static::commonSpecWriting(
            name: $name,
            pod: $pod,
            images: $images,
            volumes: $volumes,
            namespace: $namespace,
            version: $version,
            prefixer: $prefixer,
            requireLabel: $requireLabel,
            updateStrategy: fn () => match ($pod->getUpgradeStrategy()) {
                UpgradeStrategy::RollingUpgrade => [
                    'type' => 'RollingUpdate',
                    'rollingUpdate' => [
                        'maxSurge' => $pod->getMaxUpgradingPods(),
                        'maxUnavailable' => $pod->getMaxUnavailablePods(),
                    ],
                ],
                UpgradeStrategy::Recreate => [
                    'type' => 'OnDelete',
                ],
                UpgradeStrategy::OnDelete => [
                    'type' => 'OnDelete',
                ],
            },
            addServiceName: true,
        );
    }

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume>|Volume[] $volumes
     */
    private static function convertToStatefullSets(
        string $name,
        Pod $pod,
        array $images,
        array $volumes,
        string $namespace,
        int $version,
        callable $prefixer,
        string $requireLabel,
    ): StatefulSet {
        return new StatefulSet(
            static::writeSpec(
                $name,
                $pod,
                $images,
                $volumes,
                $namespace,
                $version,
                $prefixer,
                $requireLabel,
            )
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise
    ): TranscriberInterface {
        $requireLabel = $this->requireLabel;
        $compiledDeployment->foreachPod(
            static function (
                Pod $pod,
                array $images,
                array $volumes,
                string $namespace,
                string $prefix,
            ) use (
                $client,
                $promise,
                $requireLabel
            ): void {
                if ($pod->isStateless()) {
                    return;
                }

                $prefixer = self::createPrefixer($prefix);
                try {
                    if (!empty($namespace)) {
                        $client->setNamespace($namespace);
                    }

                    $name = $prefixer($pod->getName());
                    $sfsRepository = $client->statefulsets();

                    $previousStatefulSet = $sfsRepository->setLabelSelector(['name' => $name])->first();
                    $version = 1;
                    if (null !== $previousStatefulSet) {
                        $annotations = $previousStatefulSet->toArray();
                        $oldVersion = (
                            (int) substr(
                                string: ($annotations['metadata']['annotations']['teknoo.east.paas.version'] ?? 'v1'),
                                offset: 1,
                            )
                        );
                        $version = $oldVersion + 1;
                    }
                } catch (Throwable $error) {
                    $promise->fail($error);

                    return;
                }

                $kubeSet = self::convertToStatefullSets(
                    name: $name,
                    pod: $pod,
                    images: $images,
                    volumes: $volumes,
                    namespace: $namespace,
                    version: $version,
                    prefixer: $prefixer,
                    requireLabel:$requireLabel,
                );

                try {
                    $result = $sfsRepository->apply($kubeSet);

                    $result = self::cleanResult($result);

                    if (
                        null !== $previousStatefulSet
                        && UpgradeStrategy::Recreate === $pod->getUpgradeStrategy()
                    ) {
                        //If upgrade strategy is recreate, not natively available in kubernetes, we will delete current
                        //pods
                        $pods = $client->pods();
                        $labelSelector = ['vname' => $name . '-v' . $oldVersion];
                        foreach ($pods->setLabelSelector($labelSelector)->find() as $podModel) {
                            $pods->delete($podModel);
                        }
                    }

                    $promise->success($result);
                } catch (Throwable $throwable) {
                    $promise->fail($throwable);
                }
            }
        );

        return $this;
    }
}
