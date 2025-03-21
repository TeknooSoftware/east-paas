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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Parameter;
use PHPUnit\Framework\TestCase;
use Teknoo\Immutable\Exception\ImmutableException;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Parameter::class)]
class ParameterTest extends TestCase
{
    public function buildParameter()
    {
        return new Parameter('foo', 'bar');
    }

    public function testContructorUnique()
    {
        $this->expectException(\Error::class);
        $this->buildParameter()->__construct('foo', 'bar');
    }

    public function testGetName()
    {
        self::assertEquals(
            '%foo',
            $this->buildParameter()->getName()
        );
    }

    public function testGetValue()
    {
        self::assertEquals(
            'bar',
            $this->buildParameter()->getValue()
        );
    }
}
