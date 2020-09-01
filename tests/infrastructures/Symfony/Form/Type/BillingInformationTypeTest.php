<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\BillingInformationType;
use Teknoo\East\Paas\Object\BillingInformation;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\BillingInformationType
 */
class BillingInformationTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new BillingInformationType();
    }

    private function getObject()
    {
        return new BillingInformation();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'service' => 'fooBar',
            'address' => 'fooBar',
            'zip' => 'fooBar',
            'city' => 'fooBar',
            'country' => 'fooBar',
            'vat' => 'fooBar',
        ];
    }

    public function testConfigureOptions()
    {
        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->configureOptions(
                $this->createMock(OptionsResolver::class)
            )
        );
    }
}
