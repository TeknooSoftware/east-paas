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
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ClusterType;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\SshIdentity;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ClusterType
 */
class ClusterTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new ClusterType(['foo_bar' => 'Foo bar']);
    }

    private function getObject()
    {
        return new Cluster();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'address' => 'fooBar',
            'identity' => new SshIdentity(),
            'environment' => new Environment('foo'),
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
