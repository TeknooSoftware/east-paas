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

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Cluster::class)]
class ClusterTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return Cluster
     */
    public function buildObject(): Cluster
    {
        return new Cluster();
    }

    public function testGetName()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['name' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['name' => $form->setData(...)])
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'fooBar',
            (string) $this->generateObjectPopulated(['name' => 'fooBar'])
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetName()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setName('fooBar')
        );

        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['name' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['name' => $form->setData(...)])
        );
    }

    public function testSetNameExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setName(new \stdClass());
    }

    public function testSetNamespace()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setNamespace('fooBar')
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['namespace' => $form->setData(...)])
        );
    }

    public function testSetNamespaceExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setNamespace(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testUseHierarchicalNamespaces()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->useHierarchicalNamespaces(true)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with(true);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['use_hierarchical_namespaces' => $form->setData(...)])
        );
    }

    public function testUseHierarchicalNamespacesExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->useHierarchicalNamespaces(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetProject()
    {
        $argument = new Project($this->createMock(Account::class));

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setProject($argument)
        );

        $rP = new \ReflectionProperty($object, 'project');
        $rP->setAccessible(true);

        self::assertEquals(
            $argument,
            $rP->getValue($object)
        );
    }

    public function testSetProjectExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setProject(new \stdClass());
    }

    public function testGetAddress()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['address' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['address' => $form->setData(...)])
        );
    }

    public function testSetAddress()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setAddress('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['address' => $form->setData(...)])
        );
    }

    public function testGetType()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['type' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['type' => $form->setData(...)])
        );
    }

    public function testSetType()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setType('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['type' => $form->setData(...)])
        );
    }

    public function testSetEnvironment()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setEnvironment(
                $argument = $this->createMock(Environment::class)
            )
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['environment' => $form->setData(...)])
        );
    }

    public function testSetAddressExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setAddress(new \stdClass());
    }

    public function testGetIdentity()
    {
        $argument = $this->createMock(IdentityInterface::class);
        $object = $this->generateObjectPopulated(['identity' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['identity' => $form->setData(...)])
        );
    }

    public function testSetIdentity()
    {
        $argument = $this->createMock(IdentityInterface::class);

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setIdentity($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['identity' => $form->setData(...)])
        );
    }

    public function testIsLocked()
    {
        $argument = true;
        $object = $this->generateObjectPopulated(['locked' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['locked' => $form->setData(...)]),
        );

        self::assertTrue(
            $object->isLocked(),
        );
    }

    public function testSetLocked()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setLocked(true)
        );

        $argument = true;

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->visit(['locked' => $form->setData(...)])
        );
    }

    public function testSetIdentityExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setIdentity(new \stdClass());
    }

    public function testExportToMeDataBadNormalizer()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->exportToMeData(new \stdClass(), []);
    }

    public function testExportToMeDataBadContext()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createMock(EastNormalizerInterface::class),
            new \stdClass()
        );
    }

    public function testExportToMe()
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => Cluster::class,
                'id' => '123',
                'name' => 'fooName',
                'namespace' => 'foo-bar',
                'use_hierarchical_namespaces' => true,
                'type' => 'fooType',
                'address' => 'fooAddress',
                'identity' => ($identity = $this->createMock(IdentityInterface::class)),
                'environment' => ($environment = $this->createMock(Environment::class)),
                'locked' => true,
            ]);

        self::assertInstanceOf(
            Cluster::class,
            $this->buildObject()->setId('123')
                ->setName('fooName')
                ->setNamespace('foo-bar')
                ->useHierarchicalNamespaces(true)
                ->setType('fooType')
                ->setAddress('fooAddress')
                ->setIdentity($identity)
                ->setEnvironment($environment)
                ->setLocked(true)
                ->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }

    public function testPrepareJobForEnvironmentMissingEnv()
    {
        $job = $this->createMock(Job::class);
        $job->expects($this->never())->method('__call')->with('addcluster');

        $env = new Environment('foo');

        self::assertInstanceOf(
            Cluster::class,
            $this->buildObject()->prepareJobForEnvironment($job, $env)
        );
    }

    public function testPrepareJobForEnvironmentEnvNotEquals()
    {
        $job = $this->createMock(Job::class);
        $job->expects($this->never())->method('__call')->with('addCluster');

        $env = new Environment('foo');

        self::assertInstanceOf(
            Cluster::class,
            $this->buildObject()->setEnvironment(new Environment('bar'))->prepareJobForEnvironment($job, $env)
        );
    }

    public function testPrepareJobForEnvironmentEnvEquals()
    {
        $env = new Environment('foo');

        $cluster = $this->buildObject()->setEnvironment($env);

        $job = $this->createMock(Job::class);
        $job->expects($this->once())->method('addCluster')->with($cluster);

        self::assertInstanceOf(
            Cluster::class,
            $cluster->prepareJobForEnvironment($job, $env)
        );
    }

    public function testPrepareJobForEnvironmentBadJob()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->prepareJobForEnvironment(new \stdClass(), $this->createMock(Environment::class));
    }

    public function testPrepareJobForEnvironmentBadEnv()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->prepareJobForEnvironment($this->createMock(Job::class), new \stdClass());
    }

    public function testSelectClusterBadDirectory()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->selectCluster(
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testSelectClusterBadPromise()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->selectCluster(
            $this->createMock(Directory::class),
            new \stdClass()
        );
    }

    public function testSelectCluster()
    {
        $directory = $this->createMock(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        $cluster = $this->generateObjectPopulated(
            [
                'address' => $address = 'fooBar',
                'type' => 'foo',
                'identity' => $identity = $this->createMock(IdentityInterface::class),
            ]
        );

        self::assertInstanceOf(
            Cluster::class,
            $cluster->selectCluster(
                $directory,
                $this->createMock(CompiledDeploymentInterface::class),
                $promise
            )
        );
    }

    public function testConfigureClusterBadClient()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->configureCluster(
            new \stdClass(),
            $this->createMock(DefaultsBag::class),
            $this->createMock(PromiseInterface::class),
        );
    }

    public function testConfigureClusterBadPromise()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->configureCluster(
            $this->createMock(DriverInterface::class),
            $this->createMock(DefaultsBag::class),
            new \stdClass(),
        );
    }

    public function testConfigureCluster()
    {
        $client = $this->createMock(DriverInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $cluster = $this->generateObjectPopulated(
            [
                'address' => $address = 'fooBar',
                'identity' => $identity = $this->createMock(IdentityInterface::class),
            ]
        );

        $client->expects($this->once())
            ->method('configure')
            ->with($address, $identity)
            ->willReturnSelf();

        $promise->expects($this->once())->method('success')->with($client)->willReturnSelf();
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            Cluster::class,
            $cluster->configureCluster(
                $client,
                $this->createMock(DefaultsBag::class),
                $promise
            )
        );
    }

    public function testConfigureClusterOnError()
    {
        $client = $this->createMock(DriverInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $cluster = $this->generateObjectPopulated(
            [
                'address' => $address = 'fooBar',
                'identity' => $identity = $this->createMock(IdentityInterface::class),
            ]
        );

        $client->expects($this->once())
            ->method('configure')
            ->with($address, $identity)
            ->willThrowException(new \Exception());

        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail')->with(new \Exception)->willReturnSelf();

        self::assertInstanceOf(
            Cluster::class,
            $cluster->configureCluster(
                $client,
                $this->createMock(DefaultsBag::class),
                $promise
            )
        );
    }

    public function testTellMeYourEnvironment()
    {
        self::assertInstanceOf(
            Cluster::class,
            $this->buildObject()->tellMeYourEnvironment(static function() {})
        );
    }


    public function testTellMeYourEnvironmentBallCallback()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->tellMeYourEnvironment(new \stdClass());
    }
}
