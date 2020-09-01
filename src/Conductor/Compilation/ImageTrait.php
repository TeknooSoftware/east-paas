<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

trait ImageTrait
{
    /**
     * @param array<string, mixed> $original
     * @param array<string, mixed> $new
     * @return array<string, mixed>
     */
    private static function mergeConfigurations(array $original, array $new): array
    {
        foreach ($new as $key => &$value) {
            if (isset($original[$key]) && is_array($value)) {
                $original[$key] = self::mergeConfigurations($original[$key], $value);
            } else {
                $original[$key] = $value;
            }
        }

        return $original;
    }

    /**
     * @param array<string, array<string, mixed>> $imagesLibrary
     */
    private function compileImages(
        array $imagesLibrary,
        CompiledDeployment $compiledDeployment,
        JobWorkspaceInterface $workspace
    ): callable {
        return static function ($innerImagesConfigs) use ($imagesLibrary, $compiledDeployment, $workspace): void {
            $imagesConfigs = self::mergeConfigurations($innerImagesConfigs, $imagesLibrary);

            foreach ($imagesConfigs as $name => &$config) {
                $buildName = $config['build-name'] ?? $name;
                $isLibrary = !isset($innerImagesConfigs[$name]);
                $tag = (string) ($config['tag'] ?? 'lastest');
                $variables = ($config['variables'] ?? []);

                $addImage = static function ($path) use (
                    $compiledDeployment,
                    $buildName,
                    $isLibrary,
                    $tag,
                    $variables
                ) {
                    $compiledDeployment->addImage(
                        new Image(
                            $buildName,
                            $path,
                            $isLibrary,
                            $tag,
                            $variables
                        )
                    );
                };

                if (true === $isLibrary) {
                    $addImage($config['path']);

                    return;
                }

                $workspace->runInRoot(
                    fn ($root) => $addImage($root . $config['path'])
                );
            }
        };
    }
}
