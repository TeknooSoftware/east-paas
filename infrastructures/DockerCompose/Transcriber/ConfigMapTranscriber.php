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
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function count;
use function implode;
use function is_scalar;
use function reset;
use const PHP_EOL;

/**
 * "Deployment transcriber" translating CompiledDeployment's maps (key/value configuration) to Compose
 * `configs` entries backed by files pushed to the host.
 *
 * Each map option becomes a file `configs/<prefixed>-map__<key>` and a per-key Compose config; an
 * aggregated Compose config `{ <prefixed>-map: { file: ./configs/<prefixed>-map } }` (the name consumed by
 * the deployment transcribers' map references) holds the bare value (single key) or a newline-joined
 * `key=value` env-file representation (multiple keys).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ConfigMapTranscriber implements DeploymentInterface
{
    use CommonTrait;

    private const string NAME_SUFFIX = '-map';

    private static function scalarToString(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * @param array<string|int, mixed> $options
     */
    private static function aggregate(array $options): string
    {
        if (1 === count($options)) {
            return self::scalarToString(reset($options));
        }

        $lines = [];
        foreach ($options as $key => $value) {
            $lines[] = (string) $key . '=' . self::scalarToString($value);
        }

        return implode(PHP_EOL, $lines);
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        GenerationInterface $generation,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $compiledDeployment->foreachMap(
            static function (Map $map, string $prefix) use ($generation, $promise): void {
                $prefixer = self::createPrefixer($prefix);

                try {
                    $baseName = (string) $prefixer($map->getName() . self::NAME_SUFFIX);
                    $options = $map->getOptions();
                    $entries = [];

                    foreach ($options as $key => $value) {
                        $configName = $baseName . '__' . (string) $key;
                        $filePath = 'configs/' . $configName;

                        $generation
                            ->addConfig($configName, ['file' => './' . $filePath])
                            ->addFile($filePath, self::scalarToString($value));

                        $entries[$configName] = ['file' => './' . $filePath];
                    }

                    $aggregatePath = 'configs/' . $baseName;
                    $generation
                        ->addConfig($baseName, ['file' => './' . $aggregatePath])
                        ->addFile($aggregatePath, self::aggregate($options));

                    $entries[$baseName] = ['file' => './' . $aggregatePath];

                    $promise->success(['configs' => $entries]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
