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
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ProjectType;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\DockerRepository;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Object\Cluster;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ProjectType
 */
class ProjectTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new ProjectType();
    }

    private function getObject()
    {
        return (new Project(new Account()));
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'sourceRepository' => new GitRepository(),
            'imagesRepository' => new DockerRepository(),
            'clusters' => [new Cluster()],
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
