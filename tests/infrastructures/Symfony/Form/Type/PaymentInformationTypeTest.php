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
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\PaymentInformationType;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\PaymentInformation;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\PaymentInformationType
 */
class PaymentInformationTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new PaymentInformationType();
    }

    private function getOptions(): array
    {
        return [
            'account' => $this->createMock(Account::class),
        ];
    }

    private function getObject()
    {
        return new PaymentInformation();
    }

    private function getFormArray(): array
    {
        return [
            'cardHash' => 'fooBar',
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
