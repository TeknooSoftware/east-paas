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
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\GitRepositoryType;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\GitRepositoryType
 */
class GitRepositoryTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new GitRepositoryType();
    }

    private function getObject()
    {
        return new GitRepository();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'pullUrl' => 'foo',
            'defaultBranch' => 'bar',
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
