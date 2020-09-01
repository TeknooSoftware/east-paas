<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;

use DI\Container;
use DI\ContainerBuilder;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Client;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;

class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Kubernetes/di.php');

        return $containerDefinition->build();
    }

    public function testClientFactoryInterface()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            ClientFactoryInterface::class,
            $container->get(ClientFactoryInterface::class)
        );
    }

    public function testClusterClientInterface()
    {
        $container = $this->buildContainer();
        $container->set('app.job.root', '/tmp');

        self::assertInstanceOf(
            ClusterClientInterface::class,
            $container->get(ClusterClientInterface::class)
        );
    }

    public function testClient()
    {
        $container = $this->buildContainer();
        $container->set('app.job.root', '/tmp');

        self::assertInstanceOf(
            Client::class,
            $container->get(Client::class)
        );
    }
}
