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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
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

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait ImageTrait
{
    private static string $keyImageBuildName = 'build-name';
    private static string $keyImageTag = 'tag';
    private static string $keyImageVariables = 'variables';
    private static string $keyImagePath = 'path';
    private static string $valueTagLatest = 'latest';

    /**
     * @param array<string, mixed> $original
     * @param array<string, mixed> $new
     * @return array<string, mixed>
     */
    private static function mergeConfigurations(array $original, iterable $new): array
    {
        foreach ($new as $key => &$value) {
            if (isset($original[$key]) && \is_array($value)) {
                $original[$key] = self::mergeConfigurations($original[$key], $value);
            } else {
                $original[$key] = $value;
            }
        }

        return $original;
    }

    /**
     * @param iterable<string, array<string, mixed>> $imagesLibrary
     */
    private function compileImages(
        iterable $imagesLibrary,
        CompiledDeployment $compiledDeployment,
        JobWorkspaceInterface $workspace
    ): callable {
        return static function ($innerImagesConfigs) use ($imagesLibrary, $compiledDeployment, $workspace): void {
            $imagesConfigs = self::mergeConfigurations($innerImagesConfigs, $imagesLibrary);

            foreach ($imagesConfigs as $name => &$config) {
                $buildName = $config[self::$keyImageBuildName] ?? $name;
                $isLibrary = !isset($innerImagesConfigs[$name]);
                $tag = (string) ($config[self::$keyImageTag] ?? self::$valueTagLatest);
                $variables = ($config[self::$keyImageVariables] ?? []);

                $addImage = static function ($path) use (
                    $compiledDeployment,
                    $buildName,
                    $isLibrary,
                    $tag,
                    $variables
                ) {
                    $parts = \explode('/', $buildName);
                    $imageName = \array_pop($parts);

                    $image = new Image(
                        $imageName,
                        $path,
                        $isLibrary,
                        $tag,
                        $variables
                    );

                    $compiledDeployment->addImage($image);
                };

                if (true === $isLibrary) {
                    $addImage($config[self::$keyImagePath]);

                    return;
                }

                $workspace->runInRoot(
                    fn ($root) => $addImage($root . $config[self::$keyImagePath])
                );
            }
        };
    }
}
