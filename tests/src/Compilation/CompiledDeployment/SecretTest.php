<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Secret;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\Secret
 */
class SecretTest extends TestCase
{
    private function buildObject(): Secret
    {
        return new Secret('foo', 'bar', ['foo' => 'bar']);
    }

    public function testGetName()
    {
        self::assertEquals(
            'foo',
            $this->buildObject()->getName()
        );
    }

    public function testGetProvider()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getProvider()
        );
    }

    public function testGetOptions()
    {
        self::assertEquals(
            ['foo' => 'bar'],
            $this->buildObject()->getOptions()
        );
    }

    public function testGetType()
    {
        self::assertEquals(
            'default',
            $this->buildObject()->getType()
        );

        self::assertEquals(
            'tls',
            (new Secret('foo', 'bar', ['foo' => 'bar'], 'tls'))->getType()
        );
    }
}