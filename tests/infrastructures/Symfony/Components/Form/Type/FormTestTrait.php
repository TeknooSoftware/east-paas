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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\Immutable\ImmutableInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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

        $builder->expects(self::any())
            ->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = array()) use ($builder) {
                    if (DocumentType::class == $type && isset($options['query_builder'])) {
                        $qBuilder = $this->createMock(Builder::class);
                        $qBuilder->expects(self::once())
                            ->method('field')
                            ->with('deletedAt')
                            ->willReturnSelf();

                        $qBuilder->expects(self::once())
                            ->method('equals')
                            ->with(null)
                            ->willReturnSelf();

                        $repository = $this->createMock(DocumentRepository::class);
                        $repository->expects(self::once())
                            ->method('createQueryBuilder')
                            ->willReturn($qBuilder);

                        $options['query_builder']($repository);
                    }

                    return $builder;
                }
            );

        $builder->expects(self::any())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createMock(FormInterface::class);
                $event = new FormEvent($form, null);
                $callable($event);

                return $builder;
            });

        $builder->expects(self::any())
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name=>$value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects(self::any())->method('getData')->willReturn($value);
                    $children[$name] = $mock;
                }
                $form = new \ArrayIterator($children);

                $dataMapper->mapDataToForms(null, $form);
                $result = $this->getObject();
                $dataMapper->mapFormsToData($form, $result);
                self::assertInstanceOf(ObjectInterface::class, $result);

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

        $builder->expects(self::any())
            ->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = array()) use ($builder) {
                    if (DocumentType::class == $type && isset($options['query_builder'])) {
                        $qBuilder = $this->createMock(Builder::class);
                        $qBuilder->expects(self::once())
                            ->method('field')
                            ->with('deletedAt')
                            ->willReturnSelf();

                        $qBuilder->expects(self::once())
                            ->method('equals')
                            ->with(null)
                            ->willReturnSelf();

                        $repository = $this->createMock(DocumentRepository::class);
                        $repository->expects(self::once())
                            ->method('createQueryBuilder')
                            ->willReturn($qBuilder);

                        $options['query_builder']($repository);
                    }

                    if (isset($options['entry_options']['empty_data'])
                        && \is_callable($options['entry_options']['empty_data'])) {
                        $form = $this->createMock(FormInterface::class);
                        $form->expects(self::any())->method('getParent')->willReturn($form);
                        $form->expects(self::any())->method('getNormData')->willReturn($this->getObject());

                        $options['entry_options']['empty_data']($form, null);
                    }

                    return $builder;
                }
            );

        $builder->expects(self::any())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createMock(FormInterface::class);
                $event = new FormEvent($form, null);
                $callable($event);

                return $builder;
            });

        $builder->expects(self::any())
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name=>$value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects(self::any())->method('getData')->willReturn($value);
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

        $builder->expects(self::any())
            ->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = array()) use ($builder) {
                    if (DocumentType::class == $type && isset($options['query_builder'])) {
                        $qBuilder = $this->createMock(Builder::class);
                        $qBuilder->expects(self::once())
                            ->method('field')
                            ->with('deletedAt')
                            ->willReturnSelf();

                        $qBuilder->expects(self::once())
                            ->method('equals')
                            ->with(null)
                            ->willReturnSelf();

                        $repository = $this->createMock(DocumentRepository::class);
                        $repository->expects(self::once())
                            ->method('createQueryBuilder')
                            ->willReturn($qBuilder);

                        $options['query_builder']($repository);
                    }

                    return $builder;
                }
            );

        $builder->expects(self::any())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) use ($builder) {
                $form = $this->createMock(FormInterface::class);
                $event = new FormEvent($form, $this->getObject());
                $callable($event);

                return $builder;
            });

        $builder->expects(self::any())
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder) {
                $children = [];
                foreach ($this->getFormArray() as $name=>$value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects(self::any())->method('getData')->willReturn($value);
                    $children[$name] = $mock;
                }
                $form = new \ArrayIterator($children);

                $dataMapper->mapDataToForms($this->getObject(), $form);
                $result = $this->getObject();
                $dataMapper->mapFormsToData($form, $result);
                self::assertInstanceOf(ObjectInterface::class, $result);

                return $builder;
            });

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, $this->getOptions())
        );
    }
}
