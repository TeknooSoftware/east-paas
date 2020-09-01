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
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ClusterCredentialsType;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ClusterCredentialsType
 */
class ClusterCredentialsTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new ClusterCredentialsType();
    }

    private function getObject()
    {
        return new ClusterCredentials();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'serverCertificate' => 'babar',
            'privateKey' => 'barFoo',
            'publicKey' => 'bar',
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
