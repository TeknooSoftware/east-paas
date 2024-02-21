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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type;

use ArrayIterator;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\XRegistryAuthType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Object\XRegistryAuth;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\XRegistryAuthType
 */
class XRegistryAuthTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new XRegistryAuthType();
    }

    private function getObject()
    {
        return new XRegistryAuth();
    }

    private function getFormArray(): array
    {
        return [
            'username' => 'fooBar',
            'password' => 'barFoo',
            'auth' => 'barFoo',
            'clear' => false,
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

    public function testDataMapperWithSecureData()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $object = new XRegistryAuth(
            username: 'fooBar',
            password: 'barFoo',
            email: '',
            auth: 'barFoo',
            serverAddress: '',
        );

        $builder->expects(self::any())
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder, $object) {
                $children = [];
                $formArray = $this->getFormArray();
                $formArray['password'] = '';
                $formArray['clear'] = false;
                foreach ($formArray as $name=>$value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects(self::once())->method('getData')->willReturn($value);
                    $children[$name] = $mock;
                }

                $forms = new ArrayIterator($children);
                $dataMapper->mapDataToForms($object, $forms);
                $object2 = '';
                $dataMapper->mapFormsToData($forms, $object2);

                self::assertEquals(
                    $object,
                    $object2,
                );

                return $builder;
            });

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, [])
        );
    }
}
