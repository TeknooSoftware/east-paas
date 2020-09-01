<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type;

use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\XRegistryAuthType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\SshIdentity;
use Teknoo\East\Paas\Object\XRegistryAuth;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\XRegistryAuthType
 */
class XRegistryAuthTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new XRegistryAuthType();
    }

    private function getObject()
    {
        return new XRegistryAuth();
    }

    private function getFormArray(): array
    {
        return [
            'username' => 'fooBar',
            'password' => 'barFoo',
            'email' => 'bar',
            'auth' => 'barFoo',
            'serverAddress' => 'bar',
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
