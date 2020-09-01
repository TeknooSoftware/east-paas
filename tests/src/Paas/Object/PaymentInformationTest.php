<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\PaymentInformation;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Object\PaymentInformation
 */
class PaymentInformationTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return PaymentInformation
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function buildObject(): PaymentInformation
    {
        return new PaymentInformation('fooBar');
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testGsetCardHash()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated()->getCardHash()
        );
    }

    public function testSetDeletedAt()
    {
        self::markTestSkipped('Not implemented');
    }

    public function testSetDeletedAtExceptionOnBadArgument()
    {
        self::markTestSkipped('Not implemented');
    }

    public function testDeletedAt()
    {
        self::markTestSkipped('Not implemented');
    }
}
