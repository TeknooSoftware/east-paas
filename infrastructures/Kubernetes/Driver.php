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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver\Exception\UnsupportedIdentityException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver\Generator;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver\Running;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * Client driver able to perform a deployment and expose services on a kubernetes cluster from a
 * CompiledDeploymentInterface instance. This driver is built on the Kubernetes Client of Teknoo.
 *
 * This class has two state :
 * - Generator for instance created via the DI, only able to clone self
 * - Running, configured to be executed with a job, only available in a workplan.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Driver implements DriverInterface, AutomatedInterface
{
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private ?string $master = null;

    private ?ClusterCredentials $credentials = null;

    private ?DefaultsBag $defaultsBag = null;

    private ?KubernetesClient $client = null;

    private ?string $namespace = null;

    private ?bool $useHierarchicalNamespaces = null;


    public function __construct(
        private readonly ClientFactoryInterface $clientFactory,
        private readonly TranscriberCollectionInterface $transcribers,
    ) {
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
                ->with('master', new Property\IsNotEmpty())
                ->with('defaultsBag', new Property\IsNotEmpty())
                ->with('namespace', new Property\IsNotEmpty())
                ->with('useHierarchicalNamespaces', new Property\IsNotNull()),
            (new Property(Generator::class))
                ->with('master', new Property\IsEmpty())
        ];
    }

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
        $that->useHierarchicalNamespaces = $useHierarchicalNamespaces;

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
