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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use RuntimeException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Client\Generator;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Client\Running;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Maclof\Kubernetes\Client as KubernetesClient;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Client implements ClientInterface, ProxyInterface, AutomatedInterface
{
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private ClientFactoryInterface $clientFactory;

    private TranscriberCollectionInterface $transcribers;

    private ?string $master = null;

    private ?ClusterCredentials $credentials = null;

    private ?KubernetesClient $client = null;


    public function __construct(ClientFactoryInterface $clientFactory, TranscriberCollectionInterface $transcribers)
    {
        $this->clientFactory = $clientFactory;
        $this->transcribers = $transcribers;

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
                ->with('master', new Property\IsNotEmpty()),

            (new Property(Generator::class))
                ->with('master', new Property\IsEmpty()),
        ];
    }

    public function configure(string $url, ?IdentityInterface $identity): ClientInterface
    {
        if (null !== $identity && !$identity instanceof ClusterCredentials) {
            throw new RuntimeException('Not Supported');
        }

        $that = clone $this;
        $that->master = $url;
        $that->credentials = $identity;

        $that->updateStates();

        return $that;
    }

    public function deploy(CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise): ClientInterface
    {
        $this->runTranscriber($compiledDeployment, $promise, true, false);

        return $this;
    }

    public function expose(CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise): ClientInterface
    {
        $this->runTranscriber($compiledDeployment, $promise, false, true);

        return $this;
    }
}
