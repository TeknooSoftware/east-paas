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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ClusterType;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\SshIdentity;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers      \Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ClusterType
 */
class ClusterTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new ClusterType(['foo_bar' => 'Foo bar']);
    }

    private function getObject()
    {
        return new Cluster();
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'type' => 'fooBar',
            'address' => 'fooBar',
            'identity' => new SshIdentity(),
            'environment' => new Environment('foo'),
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
