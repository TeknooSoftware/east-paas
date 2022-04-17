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

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Common\Object\User as BaseUser;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Object\Account
 * @covers \Teknoo\East\Paas\Object\Account\Active
 * @covers \Teknoo\East\Paas\Object\Account\Inactive
 */
class AccountTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return Account
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function buildObject(): Account
    {
        return new Account();
    }

    public function testGetName()
    {
        $object = $this->generateObjectPopulated(['name' => 'fooBar']);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
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

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['name' => $form])
        );
    }

    public function testSetNameExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setNamespace(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetNamespace()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setNamespace('fooBar')
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['namespace' => $form])
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
    public function testNamespaceIsItDefined()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setNamespace('fooBar')
        );

        $called = false;
        self::assertInstanceOf(
            Account::class,
            $object->namespaceIsItDefined(
                function ($namespace) use (&$called) {
                    $called = true;
                    self::assertEquals('fooBar', $namespace);
                }
            )
        );

        self::assertTrue($called);
    }

    public function testNamespaceIsItDefinedOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->namespaceIsItDefined(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetProjects()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setProjects([new Project($this->createMock(Account::class))])
        );
    }

    public function testSetProjectsExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setProjects(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testGetUsers()
    {
        $argument = [new BaseUser()];
        $object = $this->generateObjectPopulated(['users' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['users' => $form])
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetUsers()
    {
        $argument = [new BaseUser()];

        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setUsers($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['users' => $form])
        );
    }

    public function testSetUsersExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setUsers(new \stdClass());
    }

    public function testCanIPrepareNewJobInactive()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects(self::never())->method('__call')->with('configure');
        $project->expects(self::once())->method('refuseExecution')
            ->with($job, 'teknoo.east.paas.error.account.inactive', $date = new \DateTime('2018-05-01'));

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()->canIPrepareNewJob($project, $job, $date, $env)
        );
    }

    public function testCanIPrepareNewJobInactiveBadProject()
    {
        $this->expectException(\TypeError::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob(new \stdClass(), $job, new \DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobInactiveBadJob()
    {
        $this->expectException(\TypeError::class);
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob($project, new \stdClass(), new \DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobInactiveBadDate()
    {
        $this->expectException(\TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob($project, $job, new \stdClass(), $env);
    }

    public function testCanIPrepareNewJobActive()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects(self::once())->method('__call')->with('configure', [$job, $date = new \DateTime('2018-05-01'), $env, 'bar']);
        $project->expects(self::never())->method('refuseExecution');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setName('foo')
                ->setNamespace('bar')
                ->canIPrepareNewJob($project, $job, $date, $env)
        );
    }

    public function testCanIPrepareNewJobActiveBadProject()
    {
        $this->expectException(\TypeError::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob(new \stdClass(), $job, new \DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobActiveBadJob()
    {
        $this->expectException(\TypeError::class);
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, new \stdClass(), new \DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobActiveBadDate()
    {
        $this->expectException(\TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, $job, new \stdClass(), $env);
    }

    public function testCanIPrepareNewJobActiveBadEnv()
    {
        $this->expectException(\TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, $job, new \DateTime('2018-05-01'), new \stdClass());
    }
    
    public function testVerifyAccessToUserInactive()
    {
        $user = $this->createMock(BaseUser::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('fail');
        $promise->expects(self::never())->method('success');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()->verifyAccessToUser($user, $promise)
        );
    }

    public function testVerifyAccessToUserActiveNotIn()
    {
        $user = $this->createMock(BaseUser::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('fail');
        $promise->expects(self::never())->method('success');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setName('foo')
                ->setNamespace('bar')
                ->verifyAccessToUser($user, $promise)
        );
    }

    public function testVerifyAccessToUserActiveIn()
    {
        $user = $this->createMock(BaseUser::class);
        $user->expects(self::any())->method('getId')->willReturn('foo');
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setName('foo')
                ->setNamespace('bar')
                ->setUsers([$user])
                ->verifyAccessToUser($user, $promise)
        );
    }
}
