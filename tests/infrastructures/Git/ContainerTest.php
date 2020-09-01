<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Git;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

/**
 * Class DefinitionProviderTest.
 */
class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Git/di.php');
        $containerDefinition->addDefinitions([
            'teknoo.paas.worker.add_history_pattern' => 'foo',
            'teknoo.paas.vendor.guzzle.verify_ssl' => true,
        ]);

        return $containerDefinition->build();
    }

    public function testCloningAgentInterface()
    {
        $container = $this->buildContainer();
        $container->set('app.job.root', '/tmp');

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $container->get(CloningAgentInterface::class)
        );
    }

    public function testGitCloningAgent()
    {
        $container = $this->buildContainer();
        $container->set('app.job.root', '/tmp');

        self::assertInstanceOf(
            CloningAgent::class,
            $container->get(CloningAgent::class)
        );
    }
}
