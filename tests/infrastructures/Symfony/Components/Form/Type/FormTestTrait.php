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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
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
 * @license     https://teknoo.software/license/mit         MIT License
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

    private function getObject()
    {
        return null;
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->any())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createMock(FormInterface::class);
                $event = new FormEvent($form, null);
                $callable($event);

                return $builder;
            });

        $builder->expects($this->any())
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name=>$value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects($this->any())->method('getData')->willReturn($value);
                    $mock->expects($this->any())->method('getName')->willReturn($name);

                    $children[$name] = $mock;
                }
                $form = new \ArrayIterator($children);

                $dataMapper->mapDataToForms(null, $form);
                $result = $this->getObject();
                $dataMapper->mapFormsToData($form, $result);
                self::assertInstanceOf(IdentifiedObjectInterface::class, $result);

                return $builder;
            });

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, $this->getOptions())
        );
    }

    public function testBuildFormBadInput()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->any())
            ->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = array()) use ($builder) {
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
                        $form = $this->createMock(FormInterface::class);
                        $form->expects($this->any())->method('getParent')->willReturn($form);
                        $form->expects($this->any())->method('getNormData')->willReturn($this->getObject());

                        $options['entry_options']['empty_data']($form, null);
                    }

                    return $builder;
                }
            );

        $builder->expects($this->any())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createMock(FormInterface::class);
                $event = new FormEvent($form, null);
                $callable($event);

                return $builder;
            });

        $builder->expects($this->any())
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name=>$value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects($this->any())->method('getData')->willReturn($value);
                    $mock->expects($this->any())->method('getName')->willReturn($name);
                    $children[$name] = $mock;
                }
                $form = new \ArrayIterator($children);

                $dataMapper->mapDataToForms(null, $form);
                $result = $this->getObject();
                if ($result instanceof ImmutableInterface) {
                    return $builder;
                }

                $result = null;
                $dataMapper->mapFormsToData($form, $result);
                self::assertEmpty($result);

                return $builder;
            });

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, $this->getOptions())
        );
    }

    public function testBuildFormSubmitted()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->any())
            ->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = array()) use ($builder) {
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

        $builder->expects($this->any())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createMock(FormInterface::class);
                $event = new FormEvent($form, $this->getObject());
                $callable($event);

                return $builder;
            });

        $builder->expects($this->any())
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name=>$value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects($this->any())->method('getData')->willReturn($value);
                    $mock->expects($this->any())->method('getName')->willReturn($name);
                    $children[$name] = $mock;
                }
                $form = new \ArrayIterator($children);

                $dataMapper->mapDataToForms($this->getObject(), $form);
                $result = $this->getObject();
                $dataMapper->mapFormsToData($form, $result);
                self::assertInstanceOf(IdentifiedObjectInterface::class, $result);

                return $builder;
            });

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, $this->getOptions())
        );
    }
}
