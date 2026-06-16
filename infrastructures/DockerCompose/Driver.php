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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerFactoryInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver\Exception\UnsupportedIdentityException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver\Generator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver\Running;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\Attributes\Assertion\Property;
use Teknoo\States\Attributes\StateClass;
use Teknoo\States\Automated\Assertion\Property\IsEmpty;
use Teknoo\States\Automated\Assertion\Property\IsNotEmpty;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * Client driver able to perform a deployment and expose services on a Docker host, over SSH and Ansible,
 * from a CompiledDeploymentInterface instance. This driver expresses the deployment as a Compose
 * Specification file and exposes services through Traefik (file provider).
 *
 * This class has two state :
 * - Generator for instance created via the DI, only able to clone self
 * - Running, configured to be executed with a job, only available in a workplan.
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
    ['master', IsNotEmpty::class],
    ['defaultsBag', IsNotEmpty::class],
    ['namespace', IsNotEmpty::class],
)]
#[Property(
    Generator::class,
    ['master', IsEmpty::class]
)]
class Driver implements DriverInterface, AutomatedInterface
{
    use ProxyTrait;
    use AutomatedTrait;

    private ?string $master = null;

    private ?ClusterCredentials $credentials = null;

    private ?DefaultsBag $defaultsBag = null;

    private ?string $namespace = null;

    public function __construct(
        private readonly RunnerFactoryInterface $runnerFactory,
        private readonly TranscriberCollectionInterface $transcribers,
    ) {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * The $useHierarchicalNamespaces flag is part of the DriverInterface contract but is ignored by this
     * driver: hierarchical namespaces are a Kubernetes-only concept with no meaning for Docker Compose.
     */
    public function configure(
        string $url,
        ?IdentityInterface $identity,
        DefaultsBag $defaultsBag,
        string $namespace,
        bool $useHierarchicalNamespaces,
    ): DriverInterface {
        if (null !== $identity && !$identity instanceof ClusterCredentials) {
            throw new UnsupportedIdentityException('Not Supported');
        }

        $that = clone $this;
        $that->master = $url;
        $that->credentials = $identity;
        $that->defaultsBag = $defaultsBag;
        $that->namespace = $namespace;

        $that->updateStates();

        return $that;
    }

    public function deploy(CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise): DriverInterface
    {
        $this->runTranscriber($compiledDeployment, $promise, true, false);

        return $this;
    }

    public function expose(CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise): DriverInterface
    {
        $this->runTranscriber($compiledDeployment, $promise, false, true);

        return $this;
    }
}
