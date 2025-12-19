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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ClusterCredentialsType;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ClusterCredentialsType::class)]
class ClusterCredentialsTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm(): ClusterCredentialsType
    {
        return new ClusterCredentialsType();
    }

    private function getObject(): ClusterCredentials
    {
        return new ClusterCredentials();
    }

    private function getFormArray(): array
    {
        return [
            'caCertificate' => 'babar',
            'clientCertificate' => 'babar',
            'clientKey' => 'babar',
            'token' => 'bar',
            'username' => 'foo',
            'password' => 'fooBar',
            'clear' => false,
        ];
    }

    public function testConfigureOptions(): void
    {
        $this->buildForm()->configureOptions(
            $this->createStub(OptionsResolver::class)
        );
        $this->assertTrue(true);
    }

    public function testDataMapperWithSecureData(): void
    {
        $builder = $this->createStub(FormBuilderInterface::class);

        $object = new ClusterCredentials(
            caCertificate: 'babar',
            clientCertificate: 'babar',
            clientKey: 'babar',
            token: 'bar',
            username: 'foo',
            password: 'fooBar',
        );

        $builder
            ->method('setDataMapper')
            ->willReturnCallback(function (DataMapperInterface $dataMapper) use ($builder, $object): MockObject|Stub {
                $children = [];
                $formArray = $this->getFormArray();
                $formArray['password'] = '';
                $formArray['token'] = '';
                $formArray['clear'] = false;
                foreach ($formArray as $name => $value) {
                    $mock = $this->createMock(FormInterface::class);
                    $mock->expects($this->once())->method('getData')->willReturn($value);
                    $children[$name] = $mock;
                }

                $forms = new ArrayIterator($children);
                $dataMapper->mapDataToForms($object, $forms);
                $object2 = '';
                $dataMapper->mapFormsToData($forms, $object2);

                $this->assertEquals($object, $object2);

                return $builder;
            });

        $this->buildForm()->buildForm($builder, []);
        $this->assertTrue(true);
    }
}
