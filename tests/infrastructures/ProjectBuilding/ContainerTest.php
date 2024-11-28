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

namespace Teknoo\Tests\East\Paas\Infrastructures\ProjectBuilding;

use ArrayObject;
use DI\Container;
use DI\ContainerBuilder;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\ComposerHook;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Exception\RuntimeException;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\MakeHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\NpmHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\PipHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\SfConsoleHook;

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
        $containerDefinition->addDefinitions(__DIR__ . '/../../../infrastructures/ProjectBuilding/di.php');

        return $containerDefinition->build();
    }

    public function testProcessFactoryInterface()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            ProcessFactoryInterface::class,
            $factory = $container->get(ProcessFactoryInterface::class)
        );

        self::assertInstanceOf(
            Process::class,
            $factory(['foo'], 'bar', 60),
        );
    }
}
