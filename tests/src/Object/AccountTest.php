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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Inactive::class)]
#[CoversClass(Active::class)]
#[CoversClass(Account::class)]
class AccountTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @throws StateNotFound
     */
    public function buildObject(): Account
    {
        return new Account();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Account::setExportConfiguration(
            [
                '@class' => ['default', 'api', 'digest', 'crud'],
                'id' => ['default', 'api', 'digest', 'crud'],
                'name' => ['default', 'api', 'digest', 'crud'],
                'namespace' => ['default', 'admin'],
                'prefixNamespace' => ['admin'],
                'quota' => ['default', 'admin'],
                'users' => ['admin'],
            ]
        );
    }

    public function testGetName(): void
    {
        $object = $this->generateObjectPopulated(['name' => 'fooBar']);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with('fooBar');

        $this->assertInstanceOf(Account::class, $object->visit('name', $form->setData(...)));
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

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with('fooBar');

        $this->assertInstanceOf(Account::class, $object->visit('name', $form->setData(...)));
    }

    public function testSetNameExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
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

        $this->assertInstanceOf(Account::class, $object->visit('namespace', $form->setData(...)));
    }

    public function testSetNamespaceExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setNamespace(new stdClass());
    }

    public function testSetQuotas(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setQuotas($a = ['compute' => ['cpu' => 5]]));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($a);

        $this->assertInstanceOf(Account::class, $object->visit('quotas', $form->setData(...)));
    }

    public function testSetQuotasExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setQuotas(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testSetPrefixNamespace(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setPrefixNamespace('fooBar'));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with('fooBar');

        $this->assertInstanceOf(Account::class, $object->visit('prefix_namespace', $form->setData(...)));
    }

    public function testSetPrefixNamespaceExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setPrefixNamespace(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testNamespaceIsItDefined(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setNamespace('foo'));

        $called = false;
        $this->assertInstanceOf(Account::class, $object->namespaceIsItDefined(
            function ($namespace, $base) use (&$called): void {
                $called = true;
                $this->assertEquals('foo', $namespace);
                $this->assertNull($base);
            }
        ));

        $this->assertTrue($called);

        $this->assertInstanceOf($object::class, $object->setPrefixNamespace('bar'));

        $called = false;
        $this->assertInstanceOf(Account::class, $object->namespaceIsItDefined(
            function ($namespace, $base) use (&$called): void {
                $called = true;
                $this->assertEquals('foo', $namespace);
                $this->assertEquals('bar', $base);
            }
        ));

        $this->assertTrue($called);
    }

    public function testNamespaceIsItDefinedOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->namespaceIsItDefined(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testSetProjects(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setProjects([new Project($this->createMock(Account::class))]));
    }

    public function testSetProjectsExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setProjects(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testGetUsers(): void
    {
        $argument = [new BaseUser()];
        $object = $this->generateObjectPopulated(['users' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Account::class, $object->visit('users', $form->setData(...)));
    }

    /**
     * @throws StateNotFound
     */
    public function testSetUsers(): void
    {
        $argument = [new BaseUser()];

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setUsers($argument));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Account::class, $object->visit('users', $form->setData(...)));
    }

    public function testSetUsersExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setUsers(new stdClass());
    }

    public function testCanIPrepareNewJobInactive(): void
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects($this->never())->method('__call')->with('configure');
        $project->expects($this->once())->method('refuseExecution')
            ->with($job, 'teknoo.east.paas.error.account.inactive', $date = new DateTime('2018-05-01'));

        $this->assertInstanceOf(Account::class, $this->buildObject()->canIPrepareNewJob($project, $job, $date, $env));
    }

    public function testCanIPrepareNewJobInactiveBadProject(): void
    {
        $this->expectException(TypeError::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob(new stdClass(), $job, new DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobInactiveBadJob(): void
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob($project, new stdClass(), new DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobInactiveBadDate(): void
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->canIPrepareNewJob($project, $job, new stdClass(), $env);
    }

    public function testCanIPrepareNewJobActive(): void
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

        $this->assertInstanceOf(Account::class, $this->buildObject()
            ->setName('foo')
            ->setNamespace('bar')
            ->setPrefixNamespace('foo')
            ->canIPrepareNewJob($project, $job, $date, $env));
    }

    public function testCanIPrepareNewJobActiveWithQuota(): void
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

        $this->assertInstanceOf(Account::class, $this->buildObject()
            ->setName('foo')
            ->setNamespace('bar')
            ->setQuotas(['compute' => ['cpu' => 5]])
            ->canIPrepareNewJob($project, $job, $date, $env));
    }

    public function testCanIPrepareNewJobActiveBadProject(): void
    {
        $this->expectException(TypeError::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob(new stdClass(), $job, new DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobActiveBadJob(): void
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, new stdClass(), new DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobActiveBadDate(): void
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, $job, new stdClass(), $env);
    }

    public function testCanIPrepareNewJobActiveBadEnv(): void
    {
        $this->expectException(TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);

        $this->buildObject()
            ->setName('foo')
            ->canIPrepareNewJob($project, $job, new DateTime('2018-05-01'), new stdClass());
    }

    public function testVerifyAccessToUserInactive(): void
    {
        $user = $this->createMock(BaseUser::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');
        $promise->expects($this->never())->method('success');

        $this->assertInstanceOf(Account::class, $this->buildObject()->verifyAccessToUser($user, $promise));
    }

    public function testVerifyAccessToUserActiveNotIn(): void
    {
        $user = $this->createMock(BaseUser::class);
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');
        $promise->expects($this->never())->method('success');

        $this->assertInstanceOf(Account::class, $this->buildObject()
            ->setName('foo')
            ->setNamespace('bar')
            ->verifyAccessToUser($user, $promise));
    }

    public function testVerifyAccessToUserActiveIn(): void
    {
        $user = $this->createMock(BaseUser::class);
        $user->method('getId')->willReturn('foo');
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(Account::class, $this->buildObject()
            ->setName('foo')
            ->setNamespace('bar')
            ->setUsers([$user])
            ->verifyAccessToUser($user, $promise));
    }

    public function testRequireAccountNamespace(): void
    {
        $this->assertInstanceOf(Account::class, $this->generateObjectPopulated(['name' => 'fooBar', 'namespace' => 'barFoo'])
            ->requireAccountNamespace(
                new class () implements AccountAwareInterface {
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
            ));
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
            $this->createMock(EastNormalizerInterface::class),
            new stdClass()
        );
    }

    public function testExportToMe(): void
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

        $this->assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setId('123')
                ->setName('fooName')
                ->exportToMeData(
                    $normalizer,
                    ['foo' => 'bar']
                )
        );
    }

    public function testSetExportConfiguration(): void
    {
        Account::setExportConfiguration($conf = ['name' => ['default']]);
        $rc = new ReflectionClass(Account::class);

        $this->assertEquals($conf, $rc->getStaticPropertyValue('exportConfigurations'));
    }
}
