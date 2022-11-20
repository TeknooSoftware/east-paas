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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Image;

use DomainException;
use RuntimeException;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Infrastructures\Image\ImageWrapper\Generator;
use Teknoo\East\Paas\Infrastructures\Image\ImageWrapper\Running;
use Teknoo\East\Paas\Infrastructures\Image\Contracts\ProcessFactoryInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Volume\Volume;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

use function array_pop;
use function explode;
use function rtrim;
use function uniqid;

/**
 * Service able to take a BuildableInterface instance and convert it / build them to OCI images and
 * push it to a registry thanks to an oci image builder.
 * Symfony Process is used to control an oci image builder, process created via a factory defined by the
 * ProcessFactoryInterface of this namespace.
 * This class has two state :
 * - Generator for instance created via the DI, only able to clone self
 * - Running, configured to be executed with a job, only available in a workplan
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ImageWrapper implements BuilderInterface, AutomatedInterface
{
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private const GRACEFUL_TIME = 30;

    private ?string $projectId = null;

    private ?string $url = null;

    private ?XRegistryAuth $auth = null;

    /**
     * @param array<string, string> $templates
     */
    public function __construct(
        private readonly string $binary,
        private readonly array $templates,
        private readonly ProcessFactoryInterface $processFactory,
        private readonly string $platforms,
        private readonly ?int $timeout,
    ) {
        foreach (['image', 'embedded-volume-image', 'volume'] as $entry) {
            if (empty($this->templates[$entry])) {
                throw new DomainException("Missing $entry template");
            }
        }

        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
            Generator::class,
            Running::class,
        ];
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property(Running::class))
                ->with('projectId', new Property\IsNotEmpty())
                ->with('url', new Property\IsNotEmpty()),

            (new Property(Generator::class))
                ->with('url', new Property\IsEmpty()),
        ];
    }

    public function configure(string $projectId, string $url, ?IdentityInterface $auth): BuilderInterface
    {
        if (null !== $auth && !$auth instanceof XRegistryAuth) {
            throw new RuntimeException('Not Supported');
        }

        $that = clone $this;
        $that->projectId = $projectId;
        $that->url = $url;
        $that->auth = $auth;

        $that->updateStates();

        return $that;
    }

    public function buildImages(
        CompiledDeploymentInterface $compiledDeployment,
        string $workingPath,
        PromiseInterface $promise
    ): BuilderInterface {
        $this->setTimeout();

        $processes = [];
        $compiledDeployment->foreachBuildable(
            function (BuildableInterface $image) use (&$processes, $workingPath, $compiledDeployment) {
                $newImage = $image->withRegistry((string) $this->getUrl());
                $compiledDeployment->updateBuildable($image, $newImage);

                $template = 'image';
                if ($newImage instanceof EmbeddedVolumeImage) {
                    $template = 'embedded-volume-image';
                }

                $script = $this->generateShellScript(
                    $newImage->getVariables(),
                    $newImage->getPath(),
                    $newImage->getUrl() . ':' . $newImage->getTag(),
                    $newImage->getName() . $this->hash($newImage->getName()),
                    $template
                );

                $path = $newImage->getPath();
                if (empty($path) && $newImage instanceof EmbeddedVolumeImage) {
                    $path = $workingPath;
                }

                $process = ($this->processFactory)($path);
                $process->setInput($script);

                $variables = $newImage->getVariables();
                $variables['PAAS_IMAGE_PLATFORM'] = $this->platforms;

                if ($newImage instanceof EmbeddedVolumeImage) {
                    $paths = [];
                    foreach ($newImage->getVolumes() as $volume) {
                        if (
                            $volume instanceof PersistentVolumeInterface
                            || !$volume instanceof Volume
                        ) {
                            continue;
                        }

                        foreach ($volume->getPaths() as $path) {
                            $parts = explode('/', rtrim($path, '/'));
                            $paths[$path] = rtrim($volume->getMountPath(), '/') . '/' . array_pop($parts);
                        }
                    }

                    $variables['PAAS_DOCKERFILE_CONTENT'] = $this->generateDockerFile(
                        $newImage->getOriginalName() . ':' . $newImage->getTag(),
                        $paths
                    );
                }

                $this->startProcess($process, $variables);

                $processes[] = $process;
            }
        );

        $this->waitProcess($processes, $promise);

        return $this;
    }

    public function buildVolumes(
        CompiledDeploymentInterface $compiledDeployment,
        string $workingPath,
        PromiseInterface $promise
    ): BuilderInterface {
        $this->setTimeout();

        $processes = [];
        $compiledDeployment->foreachVolume(
            function (string $name, VolumeInterface $volume) use (&$processes, $workingPath, $compiledDeployment) {
                if (!$volume instanceof Volume) {
                    return;
                }

                $volumeUpdated = $volume->withRegistry((string) $this->getUrl());
                $compiledDeployment->addVolume($name, $volumeUpdated);

                $script = $this->generateShellScript(
                    [],
                    $workingPath,
                    $volumeUpdated->getUrl(),
                    $volumeUpdated->getName() . $this->hash(uniqid() . $volumeUpdated->getName()),
                    'volume'
                );

                $process = ($this->processFactory)($workingPath);
                $process->setInput($script);

                $paths = [];
                foreach ($volumeUpdated->getPaths() as $path) {
                    $parts = explode('/', rtrim($path, '/'));
                    $paths[$path] = rtrim($volumeUpdated->getLocalPath(), '/') . '/' . array_pop($parts);
                }

                $variables = [
                    'PAAS_IMAGE_PLATFORM' => $this->platforms,
                    'PAAS_DOCKERFILE_CONTENT' => $this->generateDockerFile(
                        'alpine:latest',
                        $paths,
                        'cp -rf ' . $volumeUpdated->getLocalPath()
                            . '/. '
                            . $volumeUpdated->getMountPath(),
                    ),
                ];

                $this->startProcess($process, $variables);

                $processes[] = $process;
            }
        );

        $this->waitProcess($processes, $promise);

        return $this;
    }
}
