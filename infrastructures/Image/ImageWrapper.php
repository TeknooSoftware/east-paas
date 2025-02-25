<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Image;

use DomainException;
use SplQueue;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Image\EmbeddedVolumeImage;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\VolumeInterface;
use Teknoo\East\Paas\Infrastructures\Image\ImageWrapper\Exception\UnsupportedIdentityException;
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
use function bin2hex;
use function count;
use function explode;
use function rtrim;
use function random_bytes;

/**
 * Service able to take a BuildableInterface instance and convert it / build them to OCI images and
 * push it to a registry thanks to an oci image builder.
 * Symfony Process is used to control an oci image builder, process created via a factory defined by the
 * ProcessFactoryInterface of this namespace.
 * This class has two state :
 * - Generator for instance created via the DI, only able to clone self
 * - Running, configured to be executed with a job, only available in a workplan
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ImageWrapper implements BuilderInterface, AutomatedInterface
{
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

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
        private readonly ?float $timeout,
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
            throw new UnsupportedIdentityException('Not Supported');
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
        /** @var SplQueue<Process> $processes */
        $processes = new SplQueue();
        $compiledDeployment->foreachBuildable(
            function (BuildableInterface $image) use ($processes, $workingPath, $compiledDeployment): void {
                $newImage = $image->withRegistry((string) $this->getUrl());
                $compiledDeployment->updateBuildable($image, $newImage);

                $template = 'image';
                if ($newImage instanceof EmbeddedVolumeImage) {
                    $template = 'embedded-volume-image';
                }

                $script = $this->generateShellScript(
                    variables: $newImage->getVariables(),
                    path: $newImage->getPath(),
                    imageName: $newImage->getUrl() . ':' . $newImage->getTag(),
                    imageShortName: $newImage->getName() . $this->hash($newImage->getName()),
                    template: $template,
                );

                $path = $newImage->getPath();
                if (empty($path) && $newImage instanceof EmbeddedVolumeImage) {
                    $path = $workingPath;
                }

                $process = ($this->processFactory)($path);
                $process->setTimeout($this->timeout);
                $process->setInput($script);

                $variables = $newImage->getVariables();
                $variables['PAAS_IMAGE_PLATFORM'] = $this->platforms;

                if ($newImage instanceof EmbeddedVolumeImage) {
                    $paths = [];
                    $writables = [];
                    foreach ($newImage->getVolumes() as $volume) {
                        if (
                            $volume instanceof PersistentVolumeInterface
                            || !$volume instanceof Volume
                        ) {
                            continue;
                        }

                        $mP = rtrim($volume->getMountPath(), '/');
                        foreach ($volume->getPaths() as $path) {
                            $parts = explode('/', rtrim($path, '/'));
                            $dest = '';
                            if (count($parts) > 1) {
                                $dest = array_pop($parts);
                            }
                            $paths[$path] = $mP . '/' . $dest;
                        }

                        $writables = [];
                        foreach ($volume->getWritables() as $path) {
                            $writables[] = $mP . '/' . $path;
                        }
                    }

                    $variables['PAAS_DOCKERFILE_CONTENT'] = $this->generateDockerFile(
                        fromImage: $newImage->getOriginalName() . ':' . $newImage->getOriginalTag(),
                        paths: $paths,
                        writables: $writables,
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
        /** @var SplQueue<Process> $processes */
        $processes = new SplQueue();
        $compiledDeployment->foreachVolume(
            function (
                string $name,
                VolumeInterface $volume
            ) use (
                $processes,
                $workingPath,
                $compiledDeployment
            ): void {
                if (!$volume instanceof Volume) {
                    return;
                }

                $volumeUpdated = $volume->withRegistry((string) $this->getUrl());
                $compiledDeployment->addVolume($name, $volumeUpdated);

                $script = $this->generateShellScript(
                    [],
                    $workingPath,
                    $volumeUpdated->getUrl(),
                    $volumeUpdated->getName() . $this->hash(bin2hex(random_bytes(23)) . $volumeUpdated->getName()),
                    'volume'
                );

                $process = ($this->processFactory)($workingPath);
                $process->setTimeout($this->timeout);
                $process->setInput($script);

                $paths = [];
                $lP = rtrim($volumeUpdated->getLocalPath(), '/');
                foreach ($volumeUpdated->getPaths() as $path) {
                    $parts = explode('/', rtrim($path, '/'));
                    $dest = '';
                    if (count($parts) > 1) {
                        $dest = array_pop($parts);
                    }
                    $paths[$path] = $lP . '/' . $dest;
                }

                $variables = [
                    'PAAS_IMAGE_PLATFORM' => $this->platforms,
                    'PAAS_DOCKERFILE_CONTENT' => $this->generateDockerFile(
                        fromImage: 'busybox:latest',
                        paths: $paths,
                        command: 'cp -afv ' . $volumeUpdated->getLocalPath() . '/. $MOUNT_PATH'
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
