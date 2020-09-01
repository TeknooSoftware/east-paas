<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
