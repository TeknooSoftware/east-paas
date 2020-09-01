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

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Paas\Object\BillingInformation;
use Teknoo\East\Paas\Object\Account;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Object\BillingInformation
 */
class BillingInformationTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return BillingInformation
     */
    public function buildObject(): BillingInformation
    {
        return new BillingInformation();
    }

    public function testGetName()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['name' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['name' => $form])
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooBar',
            (string) $this->generateObjectPopulated(['name' => 'fooBar'])
        );
    }

    public function testSetName()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setName('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['name' => $form])
        );
    }

    public function testSetNameExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setName(new \stdClass());
    }

    public function testGetService()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['service' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['service' => $form])
        );
    }

    public function testSetService()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setService('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['service' => $form])
        );
    }

    public function testSetServiceExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setService(new \stdClass());
    }

    public function testGetAddress()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['address' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['address' => $form])
        );
    }

    public function testSetAddress()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setAddress('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['address' => $form])
        );
    }

    public function testSetAddressExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setAddress(new \stdClass());
    }

    public function testGetZip()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['zip' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['zip' => $form])
        );
    }

    public function testSetZip()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setZip('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['zip' => $form])
        );
    }

    public function testSetZipExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setZip(new \stdClass());
    }

    public function testGetCity()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['city' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['city' => $form])
        );
    }

    public function testSetCity()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setCity('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['city' => $form])
        );
    }

    public function testSetCityExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setCity(new \stdClass());
    }

    public function testGetCountry()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['country' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['country' => $form])
        );
    }

    public function testSetCountry()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setCountry('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['country' => $form])
        );
    }

    public function testSetCountryExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setCountry(new \stdClass());
    }

    public function testGetVat()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['vat' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['vat' => $form])
        );
    }

    public function testSetVat()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setVat('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            BillingInformation::class,
            $object->injectDataInto(['vat' => $form])
        );
    }

    public function testSetVatExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setVat(new \stdClass());
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
