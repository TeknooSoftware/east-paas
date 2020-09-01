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
use Teknoo\East\Website\Object\User as BaseUser;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\AccountType;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\BillingInformation;
use Teknoo\East\Paas\Object\PaymentInformation;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\AccountType
 */
class AccountTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new AccountType();
    }

    private function getObject()
    {
        return new Account();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'owner' => new BaseUser(),
            'billingInformation' => new BillingInformation(),
            'paymentInformation' => new PaymentInformation(),
            'paymentInformation' => new PaymentInformation(),
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
