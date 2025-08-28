<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type;

use ArrayIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ClusterType;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\SshIdentity;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ClusterType::class)]
class ClusterTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm(): ClusterType
    {
        return new ClusterType(['foo_bar' => 'Foo bar']);
    }

    private function getObject(): Cluster
    {
        return new Cluster();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'namespace' => 'fooBar',
            'type' => 'fooBar',
            'address' => 'fooBar',
            'identity' => new SshIdentity(),
            'environment' => new Environment('foo'),
            'locked' => false,
            'useHierarchicalNamespaces' => false,
        ];
    }

    public function testConfigureOptions(): void
    {
        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->configureOptions(
            $this->createMock(OptionsResolver::class)
        ));
    }

    public function testBuildFormSubmittedWithLocked(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $object = $this->getObject();
        $object->setLocked(true);

        $builder
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder, $object): \PHPUnit\Framework\MockObject\MockObject {
                $children = [];
                foreach ($this->getFormArray() as $name => $value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects($this->never())->method('getData');
                    $children[$name] = $mock;
                }

                $forms = new ArrayIterator($children);
                $dataMapper->mapFormsToData($forms, $object);

                return $builder;
            });

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, $this->getOptions()));
    }

    public function testBuildFormSubmittedWithUnLockedAndNotAllowedToLock(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $object = $this->getObject();
        $object->setLocked(false);

        $builder
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder, $object): \PHPUnit\Framework\MockObject\MockObject {
                $children = [];
                foreach ($this->getFormArray() as $name => $value) {
                    $mock = $this->createMock(FormInterface::class);
                    if ('locked' !== $name) {
                        $mock->expects($this->once())->method('getData')->willReturn($value);
                    } else {
                        $mock->expects($this->never())->method('getData');
                    }

                    $children[$name] = $mock;
                }

                $forms = new ArrayIterator($children);
                $dataMapper->mapFormsToData($forms, $object);

                return $builder;
            });

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, $this->getOptions()));
    }

    public function testBuildFormSubmittedWithUnLockedAndAllowedToLock(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $object = $this->getObject();
        $object->setLocked(false);

        $builder
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder, $object): \PHPUnit\Framework\MockObject\MockObject {
                $children = [];
                foreach ($this->getFormArray() as $name => $value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects($this->once())->method('getData')->willReturn($value);
                    $children[$name] = $mock;
                }

                $forms = new ArrayIterator($children);
                $dataMapper->mapFormsToData($forms, $object);

                return $builder;
            });

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, [
            'allowEditingOfLocked' => true,
        ]));
    }

    public function testSetAsReadOnlyForLockedWithoutAllowing(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->method('addEventListener')
            ->willReturnCallback(function (string $name, callable $callable) use ($builder): \PHPUnit\Framework\MockObject\MockObject {
                $config = $this->createMock(FormConfigInterface::class);
                $config
                    ->method('getType')
                    ->willReturn(
                        $this->createMock(ResolvedFormTypeInterface::class)
                    );

                $form1 = $this->createMock(Form::class);
                $form1
                    ->method('getConfig')
                    ->willReturn($config);

                $form2 = $this->createMock(Form::class);
                $form2
                    ->method('getConfig')
                    ->willReturn($config);
                $form2
                    ->method('getData')
                    ->willReturn('foo');

                $form = $this->createMock(Form::class);

                $form->expects($this->exactly(2))
                    ->method('add');

                $form
                    ->method('getIterator')
                    ->willReturn(
                        new ArrayIterator([
                            $form1,
                            $form2,
                        ])
                    );

                $object = $this->getObject();
                $object->setLocked(true);
                $event = new FormEvent($form, $object);

                $callable($event);

                return $builder;
            });

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, [
            'allowEditingOfLocked' => false,
        ]));
    }

    public function testNoSetAsReadOnlyForLockedWithAllowing(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->method('addEventListener')
            ->willReturnCallback(function (string $name, callable $callable) use ($builder): \PHPUnit\Framework\MockObject\MockObject {
                $config = $this->createMock(FormConfigInterface::class);
                $config
                    ->method('getType')
                    ->willReturn(
                        $this->createMock(ResolvedFormTypeInterface::class)
                    );

                $form = $this->createMock(Form::class);
                $form
                    ->method('getConfig')
                    ->willReturn($config);

                $form->expects($this->never())
                    ->method('add');

                $form
                    ->method('getIterator')
                    ->willReturn(new ArrayIterator([$form]));

                $object = $this->getObject();
                $object->setLocked(true);
                $event = new FormEvent($form, $object);

                $callable($event);

                return $builder;
            });

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, [
            'allowEditingOfLocked' => true,
        ]));
    }

    public function testNoSetAsReadOnlyForUnlockedWithAllowing(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->method('addEventListener')
            ->willReturnCallback(function (string $name, callable $callable) use ($builder): \PHPUnit\Framework\MockObject\MockObject {
                $config = $this->createMock(FormConfigInterface::class);
                $config
                    ->method('getType')
                    ->willReturn(
                        $this->createMock(ResolvedFormTypeInterface::class)
                    );

                $form = $this->createMock(Form::class);
                $form
                    ->method('getConfig')
                    ->willReturn($config);

                $form->expects($this->never())
                    ->method('add');

                $form
                    ->method('getIterator')
                    ->willReturn(new ArrayIterator([$form]));

                $object = $this->getObject();
                $object->setLocked(false);
                $event = new FormEvent($form, $object);

                $callable($event);

                return $builder;
            });

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, [
            'allowEditingOfLocked' => false,
        ]));
    }
}
