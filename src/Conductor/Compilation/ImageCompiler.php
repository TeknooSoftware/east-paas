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

use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Container\Image\Image;
use Teknoo\East\Paas\Contracts\Conductor\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
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
     * @var array<string, string|array<string, mixed>>
     */
    private array $imagesLibrary;

    /**
     * @param array<string, string|array<string, mixed>> $imagesLibrary
     */
    public function __construct(array $imagesLibrary)
    {
        $this->imagesLibrary = $imagesLibrary;
    }

    /**
     * @param array<string, mixed> $original
     * @param array<string, mixed> $new
     * @return array<string, mixed>
     */
    private function mergeConfigurations(array $original, iterable $new, bool $isFirstLevel = true): array
    {
        foreach ($new as $key => &$value) {
            if (isset($original[$key]) && \is_array($value)) {
                $original[$key] = static::mergeConfigurations($original[$key], $value, false);
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
        JobUnitInterface $job
    ): CompilerInterface {
        $imagesConfigs = $this->mergeConfigurations($definitions, $this->imagesLibrary);

        foreach ($imagesConfigs as $name => &$config) {
            $buildName = $config[static::KEY_BUILD_NAME] ?? $name;
            $isLibrary = isset($this->imagesLibrary[$name]);
            $tag = (string) ($config[static::KEY_TAG] ?? static::VALUE_TAG_LATEST);
            $variables = ($config[static::KEY_VARIABLES] ?? []);

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

                $compiledDeployment->addBuildable($image);
            };

            if (true === $isLibrary) {
                $addImage($config[static::KEY_PATH]);

                continue;
            }

            $workspace->runInRoot(
                fn ($root) => $addImage($root . $config[static::KEY_PATH])
            );
        }

        return $this;
    }
}
