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

use Maclof\Kubernetes\Models\Ingress;
use Maclof\Kubernetes\Models\Secret;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Client\Generator;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Client\Running;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriver\IngressTrait;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriver\ReplicationControllerTrait;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriver\SecretTrait;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Transcriver\ServiceTrait;
use Maclof\Kubernetes\Client as KubernetesClient;
use Maclof\Kubernetes\Models\ReplicationController;
use Maclof\Kubernetes\Models\Service;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Client implements ClientInterface, ProxyInterface, AutomatedInterface
{
    use ReplicationControllerTrait;
    use ServiceTrait;
    use SecretTrait;
    use IngressTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }


    private const BASE64_PREFIX = 'base64:';

    private ClientFactoryInterface $clientFactory;

    private ?string $defaultIngressClass = null;

    private ?string $defaultIngressService = null;

    private ?int $defaultIngressPort = null;

    private ?string $master = null;

    private ?ClusterCredentials $credentials = null;

    private ?KubernetesClient $client = null;

    public function __construct(
        ClientFactoryInterface $clientFactory,
        ?string $defaultIngressClass,
        ?string $defaultIngressService,
        ?int $defaultIngressPort
    ) {
        $this->clientFactory = $clientFactory;
        $this->defaultIngressClass = $defaultIngressClass;
        $this->defaultIngressService = $defaultIngressService;
        $this->defaultIngressPort = $defaultIngressPort;

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

    private function getClient(): KubernetesClient
    {
        return $this->client ?? ($this->clientFactory)(
            $this->getMasterUrl(),
            $this->getCredentials()
        );
    }

    public function configure(string $url, ?IdentityInterface $identity): ClientInterface
    {
        if (null !== $identity && !$identity instanceof ClusterCredentials) {
            throw new \RuntimeException('Not Supported');
        }

        $that = clone $this;
        $that->master = $url;
        $that->credentials = $identity;

        $that->updateStates();

        return $that;
    }

    public function deploy(CompiledDeployment $compiledDeployment, PromiseInterface $promise): ClientInterface
    {
        $client = $this->getClient();

        $this->foreachSecret(
            $compiledDeployment,
            static function (Secret $secret) use ($client, $promise) {
                try {
                    $sRepository = $client->secrets();
                    if ($sRepository->exists($secret->getMetadata('name'))) {
                        $result = $sRepository->update($secret);
                    } else {
                        $result = $sRepository->create($secret);
                    }

                    $promise->success($result);
                } catch (\Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        $this->foreachReplicationController(
            $compiledDeployment,
            static function (ReplicationController $replicationController) use ($client, $promise) {
                try {
                    $rcRepository = $client->replicationControllers();
                    if ($rcRepository->exists($replicationController->getMetadata('name'))) {
                        $result = $rcRepository->update($replicationController);
                    } else {
                        $result = $rcRepository->create($replicationController);
                    }

                    $promise->success($result);
                } catch (\Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }

    public function expose(CompiledDeployment $compiledDeployment, PromiseInterface $promise): ClientInterface
    {
        $client = $this->getClient();
        $this->foreachService(
            $compiledDeployment,
            static function (Service $service) use ($client, $promise) {
                try {
                    $serviceRepository = $client->services();
                    if ($serviceRepository->exists($service->getMetadata('name'))) {
                        $serviceRepository->delete($service);
                    }

                    $result = $serviceRepository->create($service);

                    $promise->success($result);
                } catch (\Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        $this->foreachIngress(
            $compiledDeployment,
            $this->defaultIngressClass,
            $this->defaultIngressService,
            $this->defaultIngressPort,
            static function (Ingress $ingress) use ($client, $promise) {
                try {
                    $ingressRepository = $client->ingresses();
                    if ($ingressRepository->exists($ingress->getMetadata('name'))) {
                        $result = $ingressRepository->update($ingress);
                    } else {
                        $result = $ingressRepository->create($ingress);
                    }

                    $promise->success($result);
                } catch (\Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
