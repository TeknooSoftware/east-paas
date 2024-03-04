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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Doctrine\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Paas\Infrastructures\Doctrine\Form\Type\AccountType;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\DTO\AccountQuota;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Common\Object\User as BaseUser;
use Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type\FormTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers      \Teknoo\East\Paas\Infrastructures\Doctrine\Form\Type\AccountType
 */
class AccountTypeTest extends TestCase
{
    use FormTestTrait;

    private function getOptions(): array
    {
        return [
            'doctrine_type' => ChoiceType::class
        ];
    }

    public function buildForm()
    {
        return new AccountType();
    }

    private function getObject()
    {
        return (new Account())->setQuotas(
            [
                'compute' => [
                    'cpu' => '5'
                ],
                'memory' => [
                    'memory' => '2Gi'
                ],
            ]
        );
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'namespace' => 'Foo',
            'prefix_namespace' => 'bar',
            'use_hierarchical_namespaces' => false,
            'users' => [
                new BaseUser()
            ],
            'quotas' => [
                new AccountQuota('compute', 'cpu', '5'),
                new AccountQuota('memory', 'memory', '2Gi'),
                new AccountQuota('', '', ''),
            ]
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
                    $mock->expects(self::any())->method('getName')->willReturn($name);

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
