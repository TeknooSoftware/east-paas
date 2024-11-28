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

use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Contracts\Object\Account\AccountAwareInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Account\Active;
use Teknoo\East\Paas\Object\Account\Inactive;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Common\Object\User as BaseUser;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\Proxy\Exception\StateNotFound;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use TypeError;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Inactive::class)]
#[CoversClass(Active::class)]
#[CoversClass(Account::class)]
class AccountTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return Account
     * @throws StateNotFound
     */
    public function buildObject(): Account
    {
        return new Account();
    }

    public function testStatesListDeclaration()
    {
        $rf = new ReflectionMethod(Account::class, 'statesListDeclaration');
        $rf->setAccessible(true);
        self::assertIsArray($rf->getClosure()());
    }

    public function testGetName()
    {
        $object = $this->generateObjectPopulated(['name' => 'fooBar']);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
            $object->visit('name', $form->setData(...))
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
     * @throws StateNotFound
     */
    public function testSetName()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setName('fooBar')
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
            $object->visit('name', $form->setData(...))
        );
    }

    public function testSetNameExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setName(new stdClass());
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
            Account::class,
            $object->visit('namespace', $form->setData(...))
        );
    }

    public function testSetNamespaceExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setNamespace(new stdClass());
    }

    public function testSetQuotas()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setQuotas($a = ['compute' => ['cpu' => 5]])
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($a);

        self::assertInstanceOf(
            Account::class,
            $object->visit('quotas', $form->setData(...))
        );
    }

    public function testSetQuotasExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setQuotas(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testSetPrefixNamespace()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setPrefixNamespace('fooBar')
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with('fooBar');

        self::assertInstanceOf(
            Account::class,
            $object->visit('prefix_namespace', $form->setData(...))
        );
    }

    public function testSetPrefixNamespaceExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setPrefixNamespace(new stdClass());
    }

    /**
     * @throws StateNotFound
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
        $this->expectException(TypeError::class);
        $this->buildObject()->namespaceIsItDefined(new stdClass());
    }

    /**
     * @throws StateNotFound
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
        $this->expectException(TypeError::class);
        $this->buildObject()->setProjects(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testGetUsers()
    {
        $argument = [new BaseUser()];
        $object = $this->generateObjectPopulated(['users' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->visit('users', $form->setData(...))
        );
    }

    /**
     * @throws StateNotFound
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
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->visit('users', $form->setData(...))
        );
    }

    public function testSetUsersExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setUsers(new stdClass());
    }

    public function testCanIPrepareNewJobInactive()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects($this->never())->method('__call')->with('configure');
        $project->expects($this->once())->method('refuseExecution')
            ->with($job, 'teknoo.east.paas.error.account.inactive', $date = new DateTime('2018-05-01'));

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()->canIPrepareNewJob($project, $job, $date, $env)
        );
    }

    public function testCanIPrepareNewJobInactiveBadProject()
    {
        $this->expectException(TypeError::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob(new stdClass(), $job, new DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobInactiveBadJob()
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob($project, new stdClass(), new DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobInactiveBadDate()
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob($project, $job, new stdClass(), $env);
    }

    public function testCanIPrepareNewJobActive()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects($this->once())->method('__call')->with(
            'configure',
            [
                $job,
                $date = new DateTime('2018-05-01'),
                $env,
                null,
            ]
        );
        $project->expects($this->never())->method('refuseExecution');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setName('foo')
                ->setNamespace('bar')
                ->setPrefixNamespace('foo')
                ->canIPrepareNewJob($project, $job, $date, $env)
        );
    }

    public function testCanIPrepareNewJobActiveWithQuota()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects($this->once())->method('__call')
            ->with(
                'configure',
                [
                    $job,
                    $date = new DateTime('2018-05-01'),
                    $env,
                    ['compute' => ['cpu' => 5]],
                ]
            );
        $project->expects($this->never())->method('refuseExecution');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setName('foo')
                ->setNamespace('bar')
                ->setQuotas(['compute' => ['cpu' => 5]])
                ->canIPrepareNewJob($project, $job, $date, $env)
        );
    }

    public function testCanIPrepareNewJobActiveBadProject()
    {
        $this->expectException(TypeError::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob(new stdClass(), $job, new DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobActiveBadJob()
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, new stdClass(), new DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobActiveBadDate()
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, $job, new stdClass(), $env);
    }

    public function testCanIPrepareNewJobActiveBadEnv()
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, $job, new DateTime('2018-05-01'), new stdClass());
    }
    
    public function testVerifyAccessToUserInactive()
    {
        $user = $this->createMock(BaseUser::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');
        $promise->expects($this->never())->method('success');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()->verifyAccessToUser($user, $promise)
        );
    }

    public function testVerifyAccessToUserActiveNotIn()
    {
        $user = $this->createMock(BaseUser::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');
        $promise->expects($this->never())->method('success');

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
        $user->expects($this->any())->method('getId')->willReturn('foo');
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

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
                        ): AccountAwareInterface {
                            AccountTest::assertEquals('fooBar', $name);
                            AccountTest::assertEquals('barFoo', $namespace);

                            return $this;
                        }
                    }
                )
        );
    }

    public function testExportToMeDataBadNormalizer()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(new stdClass(), []);
    }

    public function testExportToMeDataBadContext()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->exportToMeData(
            $this->createMock(EastNormalizerInterface::class),
            new stdClass()
        );
    }

    public function testExportToMe()
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => Account::class,
                'id' => '123',
                'name' => 'fooName',
                'namespace' => null,
            ]);

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()->setId('123')->setName('fooName')->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }

    public function testSetExportConfiguration()
    {
        Account::setExportConfiguration($conf = ['name' => ['default']]);
        $rc = new ReflectionClass(Account::class);

        self::assertEquals(
            $conf,
            $rc->getStaticPropertyValue('exportConfigurations'),
        );
    }
}
