<?php

declare(strict_types=1);

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

namespace Teknoo\Tests\East\Paas\Infrastructures\Git;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent;
use Teknoo\East\Paas\Infrastructures\Git\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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

        return $containerDefinition->build();
    }

    public function testFactory()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.git.cloning.timeout', 1.0);

        self::assertInstanceOf(
            ProcessFactoryInterface::class,
            $f = $container->get(ProcessFactoryInterface::class)
        );

        self::assertInstanceOf(
            Process::class,
            $f('foo'),
        );
    }

    public function testCloningAgentInterface()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $container->get(CloningAgentInterface::class)
        );
    }

    public function testGitCloningAgent()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            CloningAgent::class,
            $container->get(CloningAgent::class)
        );
    }

    public function testGitCloningAgentWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.git.cloning.timeout', 240);

        self::assertInstanceOf(
            CloningAgent::class,
            $container->get(CloningAgent::class)
        );
    }

    public function testHook()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            Hook::class,
            $container->get(Hook::class)
        );
    }

    public function testHookWithTimeout()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.git.cloning.timeout', 240);

        self::assertInstanceOf(
            Hook::class,
            $container->get(Hook::class)
        );
    }
}
