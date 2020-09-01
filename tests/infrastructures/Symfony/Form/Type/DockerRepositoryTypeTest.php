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
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\DockerRepositoryType;
use Teknoo\East\Paas\Object\DockerRepository;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\DockerRepositoryType
 */
class DockerRepositoryTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new DockerRepositoryType();
    }

    private function getObject()
    {
        return new DockerRepository();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'apiUrl' => 'foo',
            'identity' => $this->createMock(IdentityInterface::class),
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
