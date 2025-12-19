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

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\Immutable\ImmutableInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait FormTestTrait
{
    private function getOptions(): array
    {
        return [];
    }

    private function getFormArray(): array
    {
        return [];
    }

    private function getObject(): ?object
    {
        return null;
    }

    public function testBuildForm(): void
    {
        $builder = $this->createStub(FormBuilderInterface::class);

        $builder->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createStub(FormInterface::class);
                $event = new FormEvent($form, null);
                $callable($event);

                return $builder;
            });

        $builder->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name => $value) {
                    $stub = $this->createStub(FormInterface::class);
                    $stub->method('getData')->willReturn($value);
                    $stub->method('getName')->willReturn($name);

                    $children[$name] = $stub;
                }

                $form = new \ArrayIterator($children);

                $dataMapper->mapDataToForms(null, $form);
                $result = $this->getObject();
                $dataMapper->mapFormsToData($form, $result);
                $this->assertInstanceOf(IdentifiedObjectInterface::class, $result);

                return $builder;
            });

        $this->buildForm()->buildForm($builder, $this->getOptions());
        $this->assertTrue(true);
    }

    public function testBuildFormBadInput(): void
    {
        $builder = $this->createStub(FormBuilderInterface::class);

        $builder->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = []) use ($builder) {
                    if (DocumentType::class == $type && isset($options['query_builder'])) {
                        $qBuilder = $this->createMock(Builder::class);
                        $qBuilder->expects($this->once())
                            ->method('field')
                            ->with('deletedAt')
                            ->willReturnSelf();

                        $qBuilder->expects($this->once())
                            ->method('equals')
                            ->with(null)
                            ->willReturnSelf();

                        $repository = $this->createMock(DocumentRepository::class);
                        $repository->expects($this->once())
                            ->method('createQueryBuilder')
                            ->willReturn($qBuilder);

                        $options['query_builder']($repository);
                    }

                    if (isset($options['entry_options']['empty_data'])
                        && \is_callable($options['entry_options']['empty_data'])) {
                        $form = $this->createStub(FormInterface::class);
                        $form->method('getParent')->willReturn($form);
                        $form->method('getNormData')->willReturn($this->getObject());

                        $options['entry_options']['empty_data']($form, null);
                    }

                    return $builder;
                }
            );

        $builder->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createStub(FormInterface::class);
                $event = new FormEvent($form, null);
                $callable($event);

                return $builder;
            });

        $builder->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name => $value) {
                    $stub = $this->createStub(FormInterface::class);
                    $stub->method('getData')->willReturn($value);
                    $stub->method('getName')->willReturn($name);
                    $children[$name] = $stub;
                }

                $form = new \ArrayIterator($children);

                $dataMapper->mapDataToForms(null, $form);
                $result = $this->getObject();
                if ($result instanceof ImmutableInterface) {
                    return $builder;
                }

                $result = null;
                $dataMapper->mapFormsToData($form, $result);
                $this->assertEmpty($result);

                return $builder;
            });

        $this->buildForm()->buildForm($builder, $this->getOptions());
        $this->assertTrue(true);
    }

    public function testBuildFormSubmitted(): void
    {
        $builder = $this->createStub(FormBuilderInterface::class);

        $builder->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = []) use ($builder) {
                    if (DocumentType::class == $type && isset($options['query_builder'])) {
                        $qBuilder = $this->createMock(Builder::class);
                        $qBuilder->expects($this->once())
                            ->method('field')
                            ->with('deletedAt')
                            ->willReturnSelf();

                        $qBuilder->expects($this->once())
                            ->method('equals')
                            ->with(null)
                            ->willReturnSelf();

                        $repository = $this->createMock(DocumentRepository::class);
                        $repository->expects($this->once())
                            ->method('createQueryBuilder')
                            ->willReturn($qBuilder);

                        $options['query_builder']($repository);
                    }

                    return $builder;
                }
            );

        $builder->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createStub(FormInterface::class);
                $event = new FormEvent($form, $this->getObject());
                $callable($event);

                return $builder;
            });

        $builder->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name => $value) {
                    $mock = $this->createStub(FormInterface::class);
                    $mock->method('getData')->willReturn($value);
                    $mock->method('getName')->willReturn($name);
                    $children[$name] = $mock;
                }

                $form = new \ArrayIterator($children);

                $dataMapper->mapDataToForms($this->getObject(), $form);
                $result = $this->getObject();
                $dataMapper->mapFormsToData($form, $result);
                $this->assertInstanceOf(IdentifiedObjectInterface::class, $result);

                return $builder;
            });

        $this->buildForm()->buildForm($builder, $this->getOptions());
        $this->assertTrue(true);
    }
}
