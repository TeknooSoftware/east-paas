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

namespace Teknoo\East\Paas\Infrastructures\Git;

use Symplify\GitWrapper\GitWrapper;
use LogicException;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent\Generator;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent\Running;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\SshIdentity;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Workspace\File;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

use function is_object;
use function sprintf;

/**
 * Default implementation of `CloningAgentInterface`, service able to clone in a local filesystem a source repository
 * specified to a project before deploy it.
 * This agent is built on of `GitWrapper` of Symplify.
 * This class has two state :
 * - Generator for instance created via the DI, only able to clone self
 * - Running, configured to be executed with a job, only available in a workplan.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class CloningAgent implements CloningAgentInterface, ProxyInterface, AutomatedInterface
{
    use ImmutableTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private ?GitRepository $sourceRepository = null;

    private ?SshIdentity $sshIdentity = null;

    private ?JobWorkspaceInterface $workspace = null;

    /**
     * @var GitWrapper
     */
    private $gitWrapper;

    /**
     * @param GitWrapper $gitWrapper
     */
    public function __construct($gitWrapper)
    {
        $this->uniqueConstructorCheck();

        $this->gitWrapper = $gitWrapper;

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
                ->with('sourceRepository', new Property\IsNotEmpty())
                ->with('workspace', new Property\IsNotEmpty()),

            (new Property(Generator::class))
                ->with('sourceRepository', new Property\IsEmpty()),
            (new Property(Generator::class))
                ->with('workspace', new Property\IsEmpty()),
        ];
    }

    public function __clone()
    {
        $this->sourceRepository = null;
        $this->workspace = null;

        if (!empty($this->gitWrapper)) {
            $this->gitWrapper = clone $this->gitWrapper;
        }

        $this->updateStates();
    }

    public function configure(
        SourceRepositoryInterface $repository,
        JobWorkspaceInterface $workspace
    ): CloningAgentInterface {
        if (!$repository instanceof GitRepository) {
            throw new LogicException(
                sprintf("Repository of type %s is not managed by this agent", $repository::class)
            );
        }

        $identity = $repository->getIdentity();
        if (!$identity instanceof SshIdentity) {
            throw new LogicException(
                sprintf(
                    "Identity of type %s is not managed by this agent",
                    is_object($identity) ? $identity::class : 'null'
                )
            );
        }

        $that = clone $this;
        $that->sourceRepository = $repository;
        $that->sshIdentity = $identity;
        $that->workspace = $workspace;

        $that->updateStates();

        return $that;
    }

    public function run(): CloningAgentInterface
    {
        $workspace = $this->getWorkspace();
        $workspace->writeFile(
            new File('private.key', FileInterface::VISIBILITY_PRIVATE, $this->getSshIdentity()->getPrivateKey()),
            function ($path) {
                $this->gitWrapper->setPrivateKey($path);
            }
        );

        $workspace->prepareRepository($this);

        return $this;
    }

    public function cloningIntoPath(string $path): CloningAgentInterface
    {
        $sourceRepository = $this->getSourceRepository();
        $this->gitWrapper->cloneRepository(
            $sourceRepository->getPullUrl(),
            $path,
            [
                'recurse-submodules' => true,
                'branch' => $sourceRepository->getDefaultBranch()
            ]
        );

        return $this;
    }
}
