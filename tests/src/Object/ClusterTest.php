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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Object\Cluster
 */
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
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['name' => $form])
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
            \get_class($object),
            $object->setName('fooBar')
        );

        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['name' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['name' => $form])
        );
    }

    public function testSetNameExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setName(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetProject()
    {
        $argument = new Project($this->createMock(Account::class));

        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
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
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['address' => $form])
        );
    }

    public function testSetAddress()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setAddress('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['address' => $form])
        );
    }

    public function testGetType()
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['type' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['type' => $form])
        );
    }

    public function testSetType()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setType('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['type' => $form])
        );
    }

    public function testSetEnvironment()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setEnvironment(
                $argument = $this->createMock(Environment::class)
            )
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['environment' => $form])
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
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['identity' => $form])
        );
    }

    public function testSetIdentity()
    {
        $argument = $this->createMock(IdentityInterface::class);

        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setIdentity($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Cluster::class,
            $object->injectDataInto(['identity' => $form])
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
        $normalizer->expects(self::once())
            ->method('injectData')
            ->with([
                '@class' => Cluster::class,
                'id' => '123',
                'name' => 'fooName',
                'type' => 'fooType',
                'address' => 'fooAddress',
                'identity' => ($identity = $this->createMock(IdentityInterface::class)),
                'environment' => ($environment = $this->createMock(Environment::class)),
            ]);

        self::assertInstanceOf(
            Cluster::class,
            $this->buildObject()->setId('123')
                ->setName('fooName')
                ->setType('fooType')
                ->setAddress('fooAddress')
                ->setIdentity($identity)
                ->setEnvironment($environment)
                ->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }

    public function testPrepareJobForEnvironmentMissingEnv()
    {
        $job = $this->createMock(Job::class);
        $job->expects(self::never())->method('__call')->with('addcluster');

        $env = new Environment('foo');

        self::assertInstanceOf(
            Cluster::class,
            $this->buildObject()->prepareJobForEnvironment($job, $env)
        );
    }

    public function testPrepareJobForEnvironmentEnvNotEquals()
    {
        $job = $this->createMock(Job::class);
        $job->expects(self::never())->method('__call')->with('addCluster');

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
        $job->expects(self::once())->method('addCluster')->with($cluster);

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

    public function testSetDeletedAt()
    {
        self::markTestSkipped('Not implemented');
    }

    public function testSetDeletedAtExceptionOnBadArgument()
    {
        self::markTestSkipped('Not implemented');
    }

    public function testDeletedAt()
    {
        self::markTestSkipped('Not implemented');
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

        $promise->expects(self::never())->method('success');
        $promise->expects(self::never())->method('fail');

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
                $promise
            )
        );
    }

    public function testConfigureClusterBadClient()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->configureCluster(
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testConfigureClusterBadPromise()
    {
        $this->expectException(\TypeError::class);

        $this->buildObject()->configureCluster(
            $this->createMock(DriverInterface::class),
            new \stdClass()
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

        $client->expects(self::once())
            ->method('configure')
            ->with($address, $identity)
            ->willReturnSelf();

        $promise->expects(self::once())->method('success')->with($client)->willReturnSelf();
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            Cluster::class,
            $cluster->configureCluster(
                $client,
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

        $client->expects(self::once())
            ->method('configure')
            ->with($address, $identity)
            ->willThrowException(new \Exception());

        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail')->with(new \Exception)->willReturnSelf();

        self::assertInstanceOf(
            Cluster::class,
            $cluster->configureCluster(
                $client,
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
