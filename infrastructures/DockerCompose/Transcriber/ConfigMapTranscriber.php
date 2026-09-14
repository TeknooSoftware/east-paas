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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Map;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\MountedFile;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Deployment transcriber" translating CompiledDeployment's maps (key/value configuration) to Compose
 * `configs` entries backed by files pushed to the host.
 *
 * Each key of a map becomes its own Compose config
 * `{ <prefixed>-map-<key>: { file: ./configs/<prefixed>-map/<key> } }`, so a map volume can be mounted
 * exactly like Kubernetes does (one file per key under the declared mount path, see
 * `PodsTranscriberTrait::convertVolumes()`). Environment variables read from maps are handled by the pods
 * transcription (per-container env file).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConfigMapTranscriber implements DeploymentInterface
{
    use CommonTrait;
    use ValuesCollectorTrait;

    private const string NAME_SUFFIX = '-map';

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        AccumulatorInterface $accumulator,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $compiledDeployment->foreachMap(
            static function (Map $map, string $prefix) use ($accumulator, $promise): void {
                $prefixer = self::createPrefixer($prefix);

                try {
                    $baseName = (string) $prefixer($map->getName() . self::NAME_SUFFIX);
                    $emitted = [];

                    foreach ($map->getOptions() as $key => $value) {
                        $key = self::sanitizeKey((string) $key);
                        $path = 'configs/' . $baseName . '/' . $key;

                        $accumulator->addConfig(
                            $baseName . '-' . $key,
                            new MountedFile($path, self::valueToString($value)),
                        );

                        $emitted[$baseName . '-' . $key] = ['file' => './' . $path];
                    }

                    $promise->success(['configs' => $emitted]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
