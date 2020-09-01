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

namespace Teknoo\East\Paas\Infrastructures\Flysystem;

use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace\Generator;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace\Running;
use League\Flysystem\Filesystem;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\Exception\MethodNotImplemented;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Workspace implements JobWorkspaceInterface, ProxyInterface, AutomatedInterface
{
    use ImmutableTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private ?JobUnitInterface $job = null;

    private string $rootPath;

    private Filesystem $filesystem;

    private ?int $rand = null;

    public function __construct(Filesystem $filesystem, string $rootPath)
    {
        $this->uniqueConstructorCheck();

        $this->filesystem = $filesystem;
        $this->rootPath = $rootPath;

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
                ->with('job', new Property\IsNotEmpty()),

            (new Property(Generator::class))
                ->with('job', new Property\IsEmpty()),
        ];
    }

    public function __destruct()
    {
        try {
            $this->clean();
        } catch (MethodNotImplemented $error) {
            /* nothing */
        }
    }

    public function __clone()
    {
        $this->job = null;
        if ($this->filesystem instanceof Filesystem) {
            $this->filesystem = clone $this->filesystem;
        }
        $this->rand = null;

        $this->updateStates();
    }

    public function setJob(JobUnitInterface $job): JobWorkspaceInterface
    {
        $that = clone $this;

        $that->job = $job;

        $that->updateStates();
        $that->initFileSystem();

        return $that;
    }

    public function clean(): JobWorkspaceInterface
    {
        $this->doClean();

        return $this;
    }

    public function writeFile(FileInterface $file, callable $return = null): JobWorkspaceInterface
    {
        $name = $this->getWorkspacePath() . $file->getName();

        $this->filesystem->put(
            $name,
            $file->getContent(),
            [
                'visibility' => $file->getVisibility(),
            ]
        );

        if (\is_callable($return)) {
            $return($this->rootPath . $name, $file);
        }

        return $this;
    }

    private function getRand(): int
    {
        if (null === $this->rand) {
            $this->rand = \random_int(1000000, 9999999);
        }

        return $this->rand;
    }

    public function prepareRepository(CloningAgentInterface $cloningAgent): JobWorkspaceInterface
    {
        $repositoryPath = $this->getRepositoryPath();

        $cloningAgent->cloningIntoPath($this->rootPath . $repositoryPath);

        return $this;
    }

    public function loadDeploymentIntoConductor(
        ConductorInterface $conductor,
        PromiseInterface $promise
    ): JobWorkspaceInterface {
        $repositoryPath = $this->getRepositoryPath();

        $conductor->prepare(
            (string) $this->filesystem->read($repositoryPath . static::CONFIGURATION_FILE),
            $promise
        );

        return $this;
    }

    public function hasDirectory(string $path, PromiseInterface $promise): JobWorkspaceInterface
    {
        if (!empty($this->filesystem->listContents($path))) {
            $promise->success();
        } else {
            $promise->fail(new \DomainException());
        }

        return $this;
    }

    public function runInRoot(callable $callback): JobWorkspaceInterface
    {
        $repositoryPath = $this->getRepositoryPath();

        $callback(
            $this->rootPath . $repositoryPath
        );

        return $this;
    }
}
