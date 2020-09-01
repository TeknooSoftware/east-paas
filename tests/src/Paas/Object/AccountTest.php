<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\BillingInformation;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\PaymentInformation;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Website\Object\User as BaseUser;
use Teknoo\Tests\East\Website\Object\Traits\ObjectTestTrait;

/**
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
        $this->buildObject()->setName(new \stdClass());
    }

    public function testGetBillingInformation()
    {
        $info = new BillingInformation();

        $object = $this->generateObjectPopulated(['billingInformation' => $info]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($info);

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['billingInformation' => $form])
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetBillingInformation()
    {
        $argument = new BillingInformation();

        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setBillingInformation($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['billingInformation' => $form])
        );
    }

    public function testSetBillingInformationExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setBillingInformation(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testGetPaymentInformation()
    {
        $argument = new PaymentInformation('fooBar');

        $object = $this->generateObjectPopulated(['paymentInformation' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['paymentInformation' => $form])
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetPaymentInformation()
    {
        $argument = new PaymentInformation('fooBar');

        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setPaymentInformation($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['paymentInformation' => $form])
        );
    }

    public function testSetPaymentInformationExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setPaymentInformation(new \stdClass());
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

    public function testSetUsersExceptionOnBadArgument()
    {
        $this->expectException(\Exception::class);
        $this->buildObject()->setUsers(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testGetOwner()
    {
        $argument = new BaseUser();
        $object = $this->generateObjectPopulated(['owner' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['owner' => $form])
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetOwner()
    {
        $argument = new BaseUser();

        $object = $this->buildObject();
        self::assertInstanceOf(
            \get_class($object),
            $object->setOwner($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Account::class,
            $object->injectDataInto(['owner' => $form])
        );
    }

    public function testSetOwnerExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setOwner(new \stdClass());
    }

    public function testCanIPrepareNewJobInactive()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $project = $this->createMock(Project::class);
        $project->expects(self::never())->method('__call')->with('configure');
        $project->expects(self::once())->method('refuseExecution')
            ->with($job, 'teknoo.paas.error.account.inactive', $date = new \DateTime('2018-05-01'));

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
        $project->expects(self::once())->method('__call')->with('configure', [$job, $date = new \DateTime('2018-05-01'), $env]);
        $project->expects(self::never())->method('refuseExecution');

        self::assertInstanceOf(
            Account::class,
            $this->buildObject()
                ->setBillingInformation($this->createMock(BillingInformation::class))
                ->setPaymentInformation($this->createMock(PaymentInformation::class))
                ->canIPrepareNewJob($project, $job, $date, $env)
        );
    }

    public function testCanIPrepareNewJobActiveBadProject()
    {
        $this->expectException(\TypeError::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setBillingInformation($this->createMock(BillingInformation::class))
            ->setPaymentInformation($this->createMock(PaymentInformation::class))
            ->canIPrepareNewJob(new \stdClass(), $job, new \DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobActiveBadJob()
    {
        $this->expectException(\TypeError::class);
        $project = $this->createMock(Project::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setBillingInformation($this->createMock(BillingInformation::class))
            ->setPaymentInformation($this->createMock(PaymentInformation::class))
            ->canIPrepareNewJob($project, new \stdClass(), new \DateTime('2018-05-01'), $env);
    }

    public function testCanIPrepareNewJobActiveBadDate()
    {
        $this->expectException(\TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()
            ->setBillingInformation($this->createMock(BillingInformation::class))
            ->setPaymentInformation($this->createMock(PaymentInformation::class))
            ->canIPrepareNewJob($project, $job, new \stdClass(), $env);
    }

    public function testCanIPrepareNewJobActiveBadEnv()
    {
        $this->expectException(\TypeError::class);
        $project = $this->createMock(Project::class);
        $job = $this->createMock(Job::class);

        $this->buildObject()
            ->setBillingInformation($this->createMock(BillingInformation::class))
            ->setPaymentInformation($this->createMock(PaymentInformation::class))
            ->canIPrepareNewJob($project, $job, new \DateTime('2018-05-01'), new \stdClass());
    }
}
