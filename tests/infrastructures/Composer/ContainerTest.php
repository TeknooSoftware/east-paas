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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Composer;

use DI\Container;
use DI\ContainerBuilder;
use Teknoo\East\Paas\Infrastructures\Composer\ComposerHook;
use PHPUnit\Framework\TestCase;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
        $containerDefinition->addDefinitions(__DIR__.'/../../../infrastructures/Composer/di.php');

        return $containerDefinition->build();
    }

    public function testComposerHook()
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.composer.phar.path', '/foo/composer.phar');

        self::assertInstanceOf(
            ComposerHook::class,
            $container->get(ComposerHook::class)
        );
    }
}
