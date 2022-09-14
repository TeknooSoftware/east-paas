<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Compilation\Compiler;

use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

use function array_pop;
use function explode;
use function is_array;

/**
 * Compilation module able to convert `images` sections in paas.yaml file as Image instance. An Image can inherits from
 * the Image library. The Image instance will be pushed into the CompiledDeploymentInterface instance.
 * Dockerfile must be present in the source repository, in the path defined at the key `path` of this image.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ImageCompiler implements CompilerInterface
{
    private const KEY_BUILD_NAME = 'build-name';
    private const KEY_TAG = 'tag';
    private const KEY_VARIABLES = 'variables';
    private const KEY_PATH = 'path';
    private const VALUE_TAG_LATEST = 'latest';

    /**
     * @param array<string, string|array<string, mixed>> $imagesLibrary
     */
    public function __construct(
        private readonly array $imagesLibrary
    ) {
    }

    /**
     * @param array<string, mixed> $original
     * @param array<string, mixed> $new
     * @return array<string, mixed>
     */
    private function mergeConfigurations(array $original, iterable $new, bool $isFirstLevel = true): array
    {
        foreach ($new as $key => &$value) {
            if (isset($original[$key]) && is_array($value)) {
                $original[$key] = self::mergeConfigurations($original[$key], $value, false);
            } elseif (false === $isFirstLevel || isset($original[$key])) {
                $original[$key] = $value;
            }
        }

        return $original;
    }

    public function compile(
        array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job,
        ?string $storageIdentifier = null,
        ?string $defaultStorageSize = null,
        ?string $ociRegistryConfig = null,
    ): CompilerInterface {
        $imagesConfigs = $this->mergeConfigurations($definitions, $this->imagesLibrary);

        foreach ($imagesConfigs as $name => &$config) {
            $buildName = $config[self::KEY_BUILD_NAME] ?? $name;
            $isLibrary = isset($this->imagesLibrary[$name]);
            $tag = (string) ($config[self::KEY_TAG] ?? self::VALUE_TAG_LATEST);
            $variables = ($config[self::KEY_VARIABLES] ?? []);

            $addImage = static function ($path) use (
                $compiledDeployment,
                $buildName,
                $isLibrary,
                $tag,
                $variables
            ) {
                $parts = explode('/', (string) $buildName);
                $imageName = array_pop($parts);

                $image = new Image(
                    $imageName,
                    $path,
                    $isLibrary,
                    $tag,
                    $variables
                );

                $compiledDeployment->addBuildable($image);
            };

            if (true === $isLibrary) {
                $addImage($config[self::KEY_PATH]);

                continue;
            }

            $workspace->runInRoot(
                fn ($root) => $addImage($root . $config[self::KEY_PATH])
            );
        }

        return $this;
    }
}
