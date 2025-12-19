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

namespace Teknoo\Tests\East\Paas\Object;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
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
use Teknoo\States\Proxy\Exception\StateNotFound;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use Throwable;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Cluster::class)]
class ClusterTest extends TestCase
{
    use ObjectTestTrait;

    public function buildObject(): Cluster
    {
        return new Cluster();
    }

    public function testGetName(): void
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['name' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['name' => $form->setData(...)]));
    }

    public function testToString(): void
    {
        $this->assertEquals('fooBar', (string) $this->generateObjectPopulated(['name' => 'fooBar']));
    }

    /**
     * @throws StateNotFound
     */
    public function testSetName(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setName('fooBar'));

        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['name' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['name' => $form->setData(...)]));
    }

    public function testSetNameExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setName(new stdClass());
    }

    public function testSetNamespace(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setNamespace('fooBar'));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with('fooBar');

        $this->assertInstanceOf(Cluster::class, $object->visit(['namespace' => $form->setData(...)]));
    }

    public function testSetNamespaceExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setNamespace(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testUseHierarchicalNamespaces(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->useHierarchicalNamespaces(true));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with(true);

        $this->assertInstanceOf(Cluster::class, $object->visit(['use_hierarchical_namespaces' => $form->setData(...)]));
    }

    public function testUseHierarchicalNamespacesExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->useHierarchicalNamespaces(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testSetProject(): void
    {
        $argument = new Project($this->createStub(Account::class));

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setProject($argument));

        $rP = new ReflectionProperty($object, 'project');

        $this->assertEquals($argument, $rP->getValue($object));
    }

    public function testSetProjectExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setProject(new stdClass());
    }

    public function testGetAddress(): void
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['address' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['address' => $form->setData(...)]));
    }

    public function testSetAddress(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setAddress('fooBar'));

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['address' => $form->setData(...)]));
    }

    public function testGetType(): void
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['type' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['type' => $form->setData(...)]));
    }

    public function testSetType(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setType('fooBar'));

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['type' => $form->setData(...)]));
    }

    public function testSetEnvironment(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setEnvironment(
            $argument = $this->createStub(Environment::class)
        ));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['environment' => $form->setData(...)]));
    }

    public function testSetAddressExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setAddress(new stdClass());
    }

    public function testGetIdentity(): void
    {
        $argument = $this->createStub(IdentityInterface::class);
        $object = $this->generateObjectPopulated(['identity' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['identity' => $form->setData(...)]));
    }

    public function testSetIdentity(): void
    {
        $argument = $this->createStub(IdentityInterface::class);

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setIdentity($argument));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['identity' => $form->setData(...)]));
    }

    public function testIsLocked(): void
    {
        $argument = true;
        $object = $this->generateObjectPopulated(['locked' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['locked' => $form->setData(...)]));

        $this->assertTrue($object->isLocked());
    }

    public function testSetLocked(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setLocked(true));

        $argument = true;

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Cluster::class, $object->visit(['locked' => $form->setData(...)]));
    }

    public function testSetIdentityExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setIdentity(new stdClass());
    }

    public function testExportToMeDataBadNormalizer(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(new stdClass(), []);
    }

    public function testExportToMeDataBadContext(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createStub(EastNormalizerInterface::class),
            new stdClass()
        );
    }

    public function testExportToMe(): void
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
                'identity' => ($identity = $this->createStub(IdentityInterface::class)),
                'environment' => ($environment = $this->createStub(Environment::class)),
                'locked' => true,
            ]);

        $this->assertInstanceOf(Cluster::class, $this->buildObject()->setId('123')
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
            ));
    }

    public function testPrepareJobForEnvironmentMissingEnv(): void
    {
        $job = $this->createMock(Job::class);
        $job->expects($this->never())->method('__call')->with('addcluster');

        $env = new Environment('foo');

        $this->assertInstanceOf(Cluster::class, $this->buildObject()->prepareJobForEnvironment($job, $env));
    }

    public function testPrepareJobForEnvironmentEnvNotEquals(): void
    {
        $job = $this->createMock(Job::class);
        $job->expects($this->never())->method('__call')->with('addCluster');

        $env = new Environment('foo');

        $this->assertInstanceOf(Cluster::class, $this->buildObject()->setEnvironment(new Environment('bar'))->prepareJobForEnvironment($job, $env));
    }

    public function testPrepareJobForEnvironmentEnvEquals(): void
    {
        $env = new Environment('foo');

        $cluster = $this->buildObject()->setEnvironment($env);

        $job = $this->createMock(Job::class);
        $job->expects($this->once())->method('addCluster')->with($cluster);

        $this->assertInstanceOf(Cluster::class, $cluster->prepareJobForEnvironment($job, $env));
    }

    public function testPrepareJobForEnvironmentBadJob(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->prepareJobForEnvironment(new stdClass(), $this->createStub(Environment::class));
    }

    public function testPrepareJobForEnvironmentBadEnv(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->prepareJobForEnvironment($this->createStub(Job::class), new stdClass());
    }

    public function testSelectClusterBadDirectory(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->selectCluster(
            new stdClass(),
            $this->createStub(PromiseInterface::class)
        );
    }

    public function testSelectClusterBadPromise(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->selectCluster(
            $this->createStub(Directory::class),
            new stdClass()
        );
    }

    public function testSelectCluster(): void
    {
        $directory = $this->createStub(Directory::class);
        $promise = $this->createMock(PromiseInterface::class);

        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        $cluster = $this->generateObjectPopulated(
            [
                'address' => $address = 'fooBar',
                'type' => 'foo',
                'identity' => $identity = $this->createStub(IdentityInterface::class),
            ]
        );

        $this->assertInstanceOf(Cluster::class, $cluster->selectCluster(
            $directory,
            $this->createStub(CompiledDeploymentInterface::class),
            $promise
        ));
    }

    public function testConfigureClusterBadClient(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->configureCluster(
            new stdClass(),
            $this->createStub(DefaultsBag::class),
            $this->createStub(PromiseInterface::class),
        );
    }

    public function testConfigureClusterBadPromise(): void
    {
        $this->expectException(TypeError::class);

        $this->buildObject()->configureCluster(
            $this->createStub(DriverInterface::class),
            $this->createStub(DefaultsBag::class),
            new stdClass(),
        );
    }

    public function testConfigureCluster(): void
    {
        $client = $this->createMock(DriverInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $cluster = $this->generateObjectPopulated(
            [
                'address' => $address = 'fooBar',
                'identity' => $identity = $this->createStub(IdentityInterface::class),
            ]
        );

        $client->expects($this->once())
            ->method('configure')
            ->with($address, $identity)
            ->willReturnSelf();

        $promise->expects($this->once())->method('success')->with($client)->willReturnSelf();
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(Cluster::class, $cluster->configureCluster(
            $client,
            $this->createStub(DefaultsBag::class),
            $promise
        ));
    }

    public function testConfigureClusterOnError(): void
    {
        $client = $this->createMock(DriverInterface::class);
        $promise = $this->createMock(PromiseInterface::class);

        $cluster = $this->generateObjectPopulated(
            [
                'address' => $address = 'fooBar',
                'identity' => $identity = $this->createStub(IdentityInterface::class),
            ]
        );

        $client->expects($this->once())
            ->method('configure')
            ->with($address, $identity)
            ->willThrowException(new Exception());

        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail')->with(new Exception())->willReturnSelf();

        $this->assertInstanceOf(Cluster::class, $cluster->configureCluster(
            $client,
            $this->createStub(DefaultsBag::class),
            $promise
        ));
    }

    public function testTellMeYourEnvironment(): void
    {
        $this->assertInstanceOf(Cluster::class, $this->buildObject()->tellMeYourEnvironment(static function (): void {}));
    }


    public function testTellMeYourEnvironmentBallCallback(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->tellMeYourEnvironment(new stdClass());
    }
}
