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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation\Compiler\FeaturesRequirement;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirement\Set;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Set::class)]
class SetTest extends TestCase
{
    public function testValidate()
    {
        $set = new Set(['foo' => true, 'bar' => true]);

        self::assertInstanceOf(
            Set::class,
            $set->validate('foo'),
        );

        self::assertInstanceOf(
            Set::class,
            $set->validate('foo'),
        );

        self::assertInstanceOf(
            Set::class,
            $set->validate('hello'),
        );
    }

    public function testCheckIfAllRequirementsAreValidatedAllValidated()
    {
        $set = new Set(['foo' => true, 'bar' => true]);

        self::assertInstanceOf(
            Set::class,
            $set->validate('foo')->validate('bar')->checkIfAllRequirementsAreValidated()
        );
    }

    public function testCheckIfAllRequirementsAreValidatedNotAllValidated()
    {
        $set = new Set(['foo' => true, 'bar' => true]);

        self::assertInstanceOf(
            Set::class,
            $set->validate('foo'),
        );

        $this->expectException(DomainException::class);
        $set->checkIfAllRequirementsAreValidated();
    }
}