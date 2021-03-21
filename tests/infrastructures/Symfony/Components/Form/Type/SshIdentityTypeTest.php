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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\SshIdentityType;
use Teknoo\East\Paas\Object\SshIdentity;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\SshIdentityType
 */
class SshIdentityTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new SshIdentityType();
    }

    private function getObject()
    {
        return new SshIdentity();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'privateKey' => 'barFoo',
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
