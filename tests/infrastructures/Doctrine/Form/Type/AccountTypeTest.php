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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Doctrine\Form\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Infrastructures\Doctrine\Form\Type\AccountType;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Common\Object\User as BaseUser;
use Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type\FormTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
            'namespace' => 'Foo',
            'prefix_namespace' => 'bar',
            'use_hierarchical_namespaces' => false,
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
