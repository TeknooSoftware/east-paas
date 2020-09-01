<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Composer;

use DI\Container;
use DI\ContainerBuilder;
use Teknoo\East\Paas\Infrastructures\Composer\ComposerHook;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Composer/di.php');

        return $containerDefinition->build();
    }

    public function testComposerHook()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            ComposerHook::class,
            $container->get(ComposerHook::class)
        );
    }
}
