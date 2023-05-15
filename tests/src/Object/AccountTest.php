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

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Paas\Contracts\Object\Account\AccountAwareInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Common\Object\User as BaseUser;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
            $object->visit(['name' => $form->setData(...)])
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
    public function testSetUseHierarchicalNamespaces()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setUseHierarchicalNamespaces(true)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with(true);

        self::assertInstanceOf(
            Account::class,
            $object->visit(['use_hierarchical_namespaces' => $form->setData(...)])
        );
    }

    public function testSetUseHierarchicalNamespacesExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setUseHierarchicalNamespaces(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetNamespace()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setNamespace('fooBar')
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
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
    public function testSetPrefixNamespace()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setPrefixNamespace('fooBar')
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
            $object->visit(['prefix_namespace' => $form->setData(...)])
        );
    }

    public function testSetPrefixNamespaceExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setPrefixNamespace(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testNamespaceIsItDefined()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setNamespace('foo')
        );

        $called = false;
        self::assertInstanceOf(
            Account::class,
            $object->namespaceIsItDefined(
                function ($namespace, $base) use (&$called) {
                    $called = true;
                    self::assertEquals('foo', $namespace);
                    self::assertNull($base);
                }
            )
        );

        self::assertTrue($called);

        self::assertInstanceOf(
            $object::class,
            $object->setPrefixNamespace('bar')
        );

        $called = false;
        self::assertInstanceOf(
            Account::class,
            $object->namespaceIsItDefined(
                function ($namespace, $base) use (&$called) {
                    $called = true;
                    self::assertEquals('foo', $namespace);
                    self::assertEquals('bar', $base);
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
            $object::class,
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
            $object->visit(['users' => $form->setData(...)])
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
            $object::class,
            $object->setUsers($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->visit(['users' => $form->setData(...)])
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

    public function testCanIPrepareNewJobActiveWithPrefixNameSpace()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects(self::once())->method('__call')->with('configure', [$job, $date = new \DateTime('2018-05-01'), $env, 'foobar', false]);
        $project->expects(self::never())->method('refuseExecution');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setName('foo')
                ->setNamespace('bar')
                ->setPrefixNamespace('foo')
                ->canIPrepareNewJob($project, $job, $date, $env)
        );
    }

    public function testCanIPrepareNewJobActiveWithoutNameSpace()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects(self::once())->method('__call')->with('configure', [$job, $date = new \DateTime('2018-05-01'), $env, null, false]);
        $project->expects(self::never())->method('refuseExecution');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setName('foo')
                ->canIPrepareNewJob($project, $job, $date, $env)
        );
    }

    public function testCanIPrepareNewJobActiveWithHierarchicalNamespace()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects(self::once())->method('__call')->with('configure', [$job, $date = new \DateTime('2018-05-01'), $env, 'bar', true]);
        $project->expects(self::never())->method('refuseExecution');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setName('foo')
                ->setNamespace('bar')
                ->setUseHierarchicalNamespaces(true)
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

    public function testRequireAccountNamespace()
    {
        self::assertInstanceOf(
            Account::class,
            $this->generateObjectPopulated(['name' => 'fooBar', 'namespace' => 'barFoo'])
                ->requireAccountNamespace(
                    new class implements AccountAwareInterface {
                        public function passAccountNamespace(
                            Account $account,
                            ?string $name,
                            ?string $namespace,
                            ?string $prefixNamespace,
                            bool $useHierarchicalNamespaces,
                        ): AccountAwareInterface {
                            AccountTest::assertEquals('fooBar', $name);
                            AccountTest::assertEquals('barFoo', $namespace);

                            return $this;
                        }
                    }
                )
        );
    }
}
