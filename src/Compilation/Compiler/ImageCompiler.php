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

namespace Teknoo\East\Paas\Compilation\Compiler;

use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\Image;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

use function array_pop;
use function explode;
use function is_array;
use function str_replace;
use function trim;

/**
 * Compilation module able to convert `images` sections in paas.yaml file as Image instance. An Image can inherits from
 * the Image library. The Image instance will be pushed into the CompiledDeploymentInterface instance.
 * Dockerfile must be present in the source repository, in the path defined at the key `path` of this image.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): CompilerInterface {
        $imagesConfigs = $this->mergeConfigurations($definitions, $this->imagesLibrary);

        foreach ($imagesConfigs as $name => &$config) {
            $buildName = $config[self::KEY_BUILD_NAME] ?? $name;
            $isLibrary = isset($this->imagesLibrary[$name]);
            $tag = (string) ($config[self::KEY_TAG] ?? '');
            $tag = trim(str_replace(self::VALUE_TAG_LATEST, '', $tag) . '-' . $job->getEnvironmentTag(), '-');
            $variables = ($config[self::KEY_VARIABLES] ?? []);

            $addImage = static function ($path) use (
                $compiledDeployment,
                $buildName,
                $isLibrary,
                $tag,
                $variables
            ): void {
                $parts = explode('/', (string) $buildName);
                $imageName = array_pop($parts);

                $image = new Image(
                    name: $imageName,
                    path: $path,
                    library: $isLibrary,
                    tag: $tag,
                    variables: $variables
                );

                $compiledDeployment->addBuildable($image);
            };

            if (true === $isLibrary) {
                $addImage($config[self::KEY_PATH]);

                continue;
            }

            $workspace->runInRepositoryPath(
                static fn($root) => $addImage($root . $config[self::KEY_PATH])
            );
        }

        return $this;
    }
}
