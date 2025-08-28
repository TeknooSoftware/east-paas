<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Object\AccountQuota;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AccountQuota::class)]
class AccountQuotaTest extends TestCase
{
    public function testConstructor(): void
    {
        $object = new AccountQuota('cat', 'ty', 'cap');
        $this->assertEquals('cat', $object->category);
        $this->assertEquals('ty', $object->type);
        $this->assertEquals('cap', $object->capacity);
    }

    public function testJson(): void
    {
        $object = new AccountQuota('cat', 'ty', 'cap');
        $this->assertEquals(json_encode(
            [
                'category' => 'cat',
                'type' => 'ty',
                'capacity' => 'cap',
                'requires' => 'cap',
            ],
            flags: JSON_THROW_ON_ERROR
        ), json_encode($object, flags: JSON_THROW_ON_ERROR));

        $object = new AccountQuota('cat', 'ty', 'cap', 'req');
        $this->assertEquals(json_encode(
            [
                'category' => 'cat',
                'type' => 'ty',
                'capacity' => 'cap',
                'requires' => 'req',
            ],
            flags: JSON_THROW_ON_ERROR
        ), json_encode(value: $object, flags: JSON_THROW_ON_ERROR));
    }

    public function testCreate(): void
    {
        $object = new AccountQuota('cat', 'ty', 'cap');
        $this->assertEquals($object, AccountQuota::create(
            [
                'category' => 'cat',
                'type' => 'ty',
                'capacity' => 'cap',
            ]
        ));
    }
}
