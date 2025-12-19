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

namespace Teknoo\Tests\East\Paas\Infrastructures\Doctrine\Form\Type;

use ArrayIterator;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Object\User as BaseUser;
use Teknoo\East\Paas\Infrastructures\Doctrine\Form\Type\AccountType;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\AccountQuota;
use Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type\FormTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AccountType::class)]
class AccountTypeTest extends TestCase
{
    use FormTestTrait;

    private function getOptions(): array
    {
        return [
            'doctrine_type' => ChoiceType::class
        ];
    }

    public function buildForm(): AccountType
    {
        return new AccountType();
    }

    private function getObject(): Account
    {
        return new Account()->setQuotas(
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

    public function testConfigureOptions(): void
    {
        $this->buildForm()->configureOptions(
            $this->createStub(OptionsResolver::class)
        );
        $this->assertTrue(true);
    }

    public function testBuildFormSubmitted(): void
    {
        $builder = $this->createStub(FormBuilderInterface::class);

        $builder
            ->method('add')
            ->willReturnCallback(
                function (string|FormBuilderInterface $child, ?string $type, array $options = []) use ($builder): MockObject|Stub {
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

        $builder
            ->method('addEventListener')
            ->willReturnCallback(function (string $name, callable $callable) use ($builder): MockObject|Stub {
                $form = $this->createStub(FormInterface::class);
                $event = new FormEvent($form, $this->getObject());
                $callable($event);

                return $builder;
            });

        $builder
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder): MockObject|Stub {
                $children = [];
                foreach ($this->getFormArray() as $name => $value) {
                    $stub = $this->createStub(FormInterface::class);
                    $stub->method('getData')->willReturn($value);
                    $stub->method('getName')->willReturn($name);

                    $children[$name] = $stub;
                }

                $form = new ArrayIterator($children);

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
