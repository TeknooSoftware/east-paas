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

namespace Teknoo\Tests\East\Paas\Infrastructures\Doctrine\Form\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Infrastructures\Doctrine\Form\Type\AccountType;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Website\Object\User as BaseUser;
use Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type\FormTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Doctrine\Form\Type\AccountType
 */
class AccountTypeTest extends TestCase
{
    use FormTestTrait;

    private function getOptions(): array
    {
        return [
            'doctrine_type' => ChoiceType::class
        ];
    }

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
            'namespace' => 'barFoo',
            'users' => [new BaseUser()],
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
