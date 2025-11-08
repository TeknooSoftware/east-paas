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

namespace Teknoo\East\Paas\Infrastructures\Flysystem;

use DomainException;
use Exception;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace\Generator;
use Teknoo\East\Paas\Infrastructures\Flysystem\Workspace\Running;
use League\Flysystem\Filesystem;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\States\Attributes\Assertion\Property;
use Teknoo\States\Attributes\StateClass;
use Teknoo\States\Automated\Assertion\Property\IsEmpty;
use Teknoo\States\Automated\Assertion\Property\IsNotEmpty;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;
use Throwable;

use function is_callable;
use function random_int;
use function substr;

/**
 * Implementation of `JobWorkspaceInterface` to represent the dedicated file system manager used locally to perform the
 * deployment, clone source, prepare deployment (get vendors, compile, do some stuf, etc...) compile oci images.,
 * This implementation is built on `FlySystem` of the PHP League.
 * This class has two state :
 * - Generator for instance created via the DI, only able to clone self
 * - Running, configured to be executed with a job, only available in a workplan
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[StateClass(Generator::class)]
#[StateClass(Running::class)]
#[Property(
    Running::class,
    ['job', IsNotEmpty::class],
)]
#[Property(
    Generator::class,
    ['job', IsEmpty::class],
)]
class Workspace implements JobWorkspaceInterface, AutomatedInterface
{
    use ImmutableTrait;
    use ProxyTrait;
    use AutomatedTrait;

    private ?JobUnitInterface $job = null;

    private ?int $rand = null;

    private readonly string $rootPath;

    public function __construct(
        private Filesystem $filesystem,
        string $rootPath,
        private readonly string $configurationFileName,
    ) {
        $this->uniqueConstructorCheck();

        if (str_ends_with($rootPath, '/') || str_ends_with($rootPath, '\\')) {
            $rootPath = substr(string: $rootPath, offset: 0, length: -1);
        }

        $this->rootPath = $rootPath;

        $this->initializeStateProxy();
        $this->updateStates();
    }

    public function __destruct()
    {
        try {
            $this->clean();
        } catch (Throwable) {
            /* nothing */
        }
    }

    public function __clone()
    {
        $this->job = null;
        $this->filesystem = clone $this->filesystem;

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

    public function writeFile(FileInterface $file, ?callable $return = null): JobWorkspaceInterface
    {
        $path = $this->getWorkspacePath();
        $name =  $file->getName();

        $this->filesystem->write(
            $path . $name,
            $file->getContent(),
            [
                'visibility' => $file->getVisibility()->value,
            ]
        );

        if (is_callable($return)) {
            $return($this->rootPath . $path, $name, $file);
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    private function getRand(): int
    {
        if (null === $this->rand) {
            $this->rand = random_int(1_000_000, 9_999_999);
        }

        return $this->rand;
    }

    public function prepareRepository(CloningAgentInterface $cloningAgent): JobWorkspaceInterface
    {
        $cloningAgent->cloningIntoPath($this->rootPath . $this->getWorkspacePath(), $this->getRepositoryPath());

        return $this;
    }

    public function loadDeploymentIntoConductor(
        ConductorInterface $conductor,
        PromiseInterface $promise
    ): JobWorkspaceInterface {
        $repositoryPath = $this->getWorkspacePath() . $this->getRepositoryPath();

        $conductor->prepare(
            $this->filesystem->read($repositoryPath . $this->configurationFileName),
            $promise
        );

        return $this;
    }

    public function hasDirectory(string $path, PromiseInterface $promise): JobWorkspaceInterface
    {
        foreach ($this->filesystem->listContents($path) as $item) {
            $promise->success();

            return $this;
        }

        $promise->fail(new DomainException("Diretory {$path} does not exist"));

        return $this;
    }

    public function runInRepositoryPath(callable $callback): JobWorkspaceInterface
    {
        $workspacePath = $this->rootPath . $this->getWorkspacePath();
        $callback(
            $workspacePath . $this->getRepositoryPath(),
            $workspacePath,
        );

        return $this;
    }
}
