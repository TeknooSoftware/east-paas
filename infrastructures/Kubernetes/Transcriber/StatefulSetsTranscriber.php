<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\UpgradeStrategy;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Kubernetes\Model\StatefulSet;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function substr;

/**
 * "Stateful Sets transcriber" to translate CompiledDeployment's pods and containers to Kubernetes ReplicationsSet
 * manifest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class StatefulSetsTranscriber implements DeploymentInterface
{
    use CommonTrait;
    use PodsTranscriberTrait;

    private const string NAME_SUFFIX = '-sfset';

    private const string POD_SUFFIX = '-pod';

    private const string VOLUME_SUFFIX = '-volume';

    private const string SECRET_SUFFIX = '-secret';

    private const string MAP_SUFFIX = '-map';


    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume> $volumes
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
        DefaultsBag $defaultsBag,
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
            updateStrategy: fn (): array => match ($pod->getUpgradeStrategy()) {
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
            defaultsBag: $defaultsBag,
        );
    }

    /**
     * @param array<string, array<string, Image>>|Image[][] $images
     * @param array<string, Volume> $volumes
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
        defaultsBag $defaultsBag,
    ): StatefulSet {
        return new StatefulSet(
            static::writeSpec(
                name: $name,
                pod: $pod,
                images: $images,
                volumes: $volumes,
                namespace: $namespace,
                version: $version,
                prefixer: $prefixer,
                requireLabel: $requireLabel,
                defaultsBag: $defaultsBag,
            )
        );
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        KubernetesClient $client,
        PromiseInterface $promise,
        defaultsBag $defaultsBag,
        string $namespace,
        bool $useHierarchicalNamespaces,
    ): TranscriberInterface {
        $requireLabel = $this->requireLabel;
        $compiledDeployment->foreachPod(
            static function (
                Pod $pod,
                array $images,
                array $volumes,
                string $prefix,
            ) use (
                $client,
                $namespace,
                $promise,
                $requireLabel,
                $defaultsBag,
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

                    $previousStatefulSet = null;
                    $oldVersion = 0;
                    $version = self::getVersion($name, $sfsRepository, $oldVersion, $previousStatefulSet);
                } catch (Throwable $error) {
                    $promise->fail($error);

                    return;
                }

                /** @var array<array<Image>> $images */
                /** @var array<string, Volume> $volumes */
                $kubeSet = self::convertToStatefullSets(
                    name: $name,
                    pod: $pod,
                    images: $images,
                    volumes: $volumes,
                    namespace: $namespace,
                    version: $version,
                    prefixer: $prefixer,
                    requireLabel:$requireLabel,
                    defaultsBag: $defaultsBag,
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
                        /** @var Repository<StatefulSet> $pods */
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
