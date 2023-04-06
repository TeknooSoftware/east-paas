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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Object;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\States\Exception\MethodNotImplemented;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Object\Project
 * @covers \Teknoo\East\Paas\Object\Project\Draft
 * @covers \Teknoo\East\Paas\Object\Project\Executable
 */
class ProjectTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return Project
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function buildObject(): Project
    {
        return new Project($this->createMock(Account::class));
    }

    public function testConstructor()
    {
        $object = $this->buildObject();

        $rP = new \ReflectionProperty($object, 'account');
        $rP->setAccessible(true);

        self::assertInstanceOf(
            Account::class,
            $rP->getValue($object)
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testGetAccount()
    {
        $object = new Account();
        self::assertEquals(
            $object,
            $this->generateObjectPopulated(['account' => $object])->getAccount()
        );
    }

    public function testGetAccountNoAccount()
    {
        $this->expectException(\RuntimeException::class);
        (new Project())->getAccount();
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
            Project::class,
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

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit(['name' => $form->setData(...)])
        );
    }

    public function testSetNameExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setName(new \stdClass());
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetPrefix()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setPrefix('fooBar')
        );

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit(['prefix' => $form->setData(...)])
        );
    }

    public function testSetPrefixExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setPrefix(new \stdClass());
    }

    public function testGetSourceRepository()
    {
        $argument = $this->createMock(SourceRepositoryInterface::class);
        $object = $this->generateObjectPopulated(['sourceRepository' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit(['sourceRepository' => $form->setData(...)])
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetSourceRepository()
    {
        $argument = $this->createMock(SourceRepositoryInterface::class);

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setSourceRepository($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit(['sourceRepository' => $form->setData(...)])
        );
    }

    public function testSetSourceRepositoryExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setSourceRepository(new \stdClass());
    }

    public function testGetImagesRegistry()
    {
        $argument = $this->createMock(ImageRegistryInterface::class);
        $object = $this->generateObjectPopulated(['imagesRegistry' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit(['imagesRegistry' => $form->setData(...)])
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetImagesRegistry()
    {
        $argument = $this->createMock(ImageRegistryInterface::class);

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setImagesRegistry($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit(['imagesRegistry' => $form->setData(...)])
        );
    }

    public function testSetImagesRegistryExceptionOnBadArgument()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->setImagesRegistry(new \stdClass());
    }

    public function testGetClusters()
    {
        $argument = [new Cluster()];
        $object = $this->generateObjectPopulated(['clusters' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit(['clusters' => $form->setData(...)])
        );
    }

    /**
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function testSetClusters()
    {
        $object = $this->buildObject();
        $argument = [new Cluster()];
        self::assertInstanceOf(
            $object::class,
            $object->setClusters($argument)
        );

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit(['clusters' => $form->setData(...)])
        );
    }

    public function testSetClustersExceptionOnBadArgument()
    {
        $this->expectException(\Throwable::class);
        $this->buildObject()->setClusters(new \stdClass());
    }

    public function testUpdateClusters()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->updateClusters()
        );
    }

    public function testRefuseExecution()
    {
        $job = $this->createMock(Job::class);
        $job->expects(self::once())->method('addToHistory')->with('foo', new \DateTimeImmutable('2018-05-01'), true);

        self::assertInstanceOf(
            Project::class,
            $this->buildObject()->refuseExecution($job, 'foo', new \DateTimeImmutable('2018-05-01'))
        );
    }

    public function testRefuseExecutionBadJob()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->refuseExecution(new \stdClass(), 'foo', new \DateTimeImmutable('2018-05-01'));
    }

    public function testRefuseExecutionBadError()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->refuseExecution($this->createMock(Job::class), new \stdClass(), new \DateTimeImmutable('2018-05-01'));
    }

    public function testRefuseExecutionBadDate()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()->refuseExecution($this->createMock(Job::class), 'foo', new \stdClass());
    }

    public function testPrepareJobProjectIsDraft()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);
        $account = $this->createMock(Account::class);

        $account->expects(self::never())->method('__call')->with('canIPrepareNewJob');
        $job->expects(self::once())->method('addToHistory')
            ->with('teknoo.east.paas.error.project.not_executable', $date = new \DateTime('2018-05-01'), true);

        self::assertInstanceOf(
            Project::class,
            (new Project($account))
                ->prepareJob($job, $date, $env)
        );
    }

    public function testPrepareJobProjectIsDraftBadJob()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->prepareJob(new \stdClass(), $this->createMock(Environment::class));
    }


    public function testPrepareJobProjectIsDraftBadDate()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->prepareJob($this->createMock(Job::class), $this->createMock(Environment::class), new \stdClass());
    }

    public function testPrepareJobProjectIsExecutable()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);
        $account = $this->createMock(Account::class);

        $project = (new Project($account));

        $account->expects(self::once())->method('__call')->with('canIPrepareNewJob', [$project, $job, $date = new \DateTime('2018-05-01'), $env]);
        $job->expects(self::never())->method('addToHistory');

        self::assertInstanceOf(
            Project::class,
            $project
                ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
                ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
                ->setClusters([$this->createMock(Cluster::class)])
                ->prepareJob($job, $date, $env)
        );
    }

    public function testPrepareJobProjectIsExecutableBadJob()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->prepareJob(new \stdClass(), new \DateTime('2018-05-01'), $this->createMock(Environment::class));
    }

    public function testPrepareJobProjectIsExecutableBadEnv()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->prepareJob($this->createMock(Job::class), new \DateTime('2018-05-01'), new \stdClass());
    }

    public function testPrepareJobProjectIsExecutableBadDate()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->prepareJob($this->createMock(Job::class), new \stdClass(), $this->createMock(Environment::class));
    }

    public function testConfigureProjectIsDraft()
    {
        $this->expectException(MethodNotImplemented::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->configure($job, $env);
    }

    public function testConfigureProjectIsExecutable()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);
        $sourceRepository = $this->createMock(SourceRepositoryInterface::class);
        $imagesRegistry = $this->createMock(ImageRegistryInterface::class);
        $cluster1 = $this->createMock(Cluster::class);
        $cluster2 = $this->createMock(Cluster::class);

        $cluster1->expects(self::once())->method('prepareJobForEnvironment')
            ->with($job, $env);
        $cluster2->expects(self::once())->method('prepareJobForEnvironment')
            ->with($job, $env);

        $project = $this->buildObject();
        $job->expects(self::atLeastOnce())
            ->method('setProject')
            ->with($project)
            ->willReturnSelf();

        $job->expects(self::atLeastOnce())
            ->method('setEnvironment')
            ->with($env)
            ->willReturnSelf();

        $job->expects(self::atLeastOnce())
            ->method('setSourceRepository')
            ->with($sourceRepository)
            ->willReturnSelf();

        $job->expects(self::atLeastOnce())
            ->method('setImagesRegistry')
            ->with($imagesRegistry)
            ->willReturnSelf();

        self::assertInstanceOf(
            Project::class,
            $project
                ->setSourceRepository($sourceRepository)
                ->setImagesRegistry($imagesRegistry)
                ->setClusters([$cluster1, $cluster2])
                ->configure($job, new \DateTime('2018-05-01'), $env, 'default', false)
        );
    }

    public function testConfigureProjectIsExecutableBadJob()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure(new \stdClass(), new \DateTime('2018-05-01'), $this->createMock(Environment::class));
    }

    public function testConfigureProjectIsExecutableBadEnv()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure($this->createMock(Job::class), new \DateTime('2018-05-01'), new \stdClass());
    }

    public function testConfigureProjectIsExecutableBadDate()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure($this->createMock(Job::class), new \stdClass(), $this->createMock(Environment::class));
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

    public function testListMeYourEnvironments()
    {
        self::assertInstanceOf(
            Project::class,
            $this->buildObject()
                ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
                ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
                ->setClusters([$this->createMock(Cluster::class)])
                ->listMeYourEnvironments(
                static function() { }
            )
        );
    }

    public function testListMeYourEnvironmentsBadCallable()
    {
        $this->expectException(\TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->listMeYourEnvironments(
            new \stdClass()
        );
    }

    public function testExportToMe()
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects(self::once())
            ->method('injectData')
            ->with([
                '@class' => Project::class,
                'id' => '123',
                'name' => 'fooName',
            ]);

        self::assertInstanceOf(
            Project::class,
            $this->buildObject()->setId('123')->setName('fooName')->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            )
        );
    }
}
