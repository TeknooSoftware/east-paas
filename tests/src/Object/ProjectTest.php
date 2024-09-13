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

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\Project\Draft;
use Teknoo\East\Paas\Object\Project\Executable;
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\States\Exception\MethodNotImplemented;
use Teknoo\States\Proxy\Exception\StateNotFound;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use Throwable;
use TypeError;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversTrait(ExportConfigurationsTrait::class)]
#[CoversClass(Executable::class)]
#[CoversClass(Draft::class)]
#[CoversClass(Project::class)]
class ProjectTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return Project
     * @throws StateNotFound
     */
    public function buildObject(): Project
    {
        return new Project($this->createMock(Account::class));
    }

    public function testStatesListDeclaration()
    {
        $rf = new ReflectionMethod(Project::class, 'statesListDeclaration');
        $rf->setAccessible(true);
        self::assertIsArray($rf->getClosure()());
    }

    public function testConstructor()
    {
        $object = $this->buildObject();

        $rP = new ReflectionProperty($object, 'account');
        $rP->setAccessible(true);

        self::assertInstanceOf(
            Account::class,
            $rP->getValue($object)
        );
    }

    /**
     * @throws StateNotFound
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
        $this->expectException(RuntimeException::class);
        (new Project())->getAccount();
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
            Project::class,
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

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit('name', $form->setData(...))
        );
    }

    public function testSetNameExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setName(new stdClass());
    }

    /**
     * @throws StateNotFound
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
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit('prefix', $form->setData(...))
        );
    }

    public function testSetPrefixExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setPrefix(new stdClass());
    }

    public function testGetSourceRepository()
    {
        $argument = $this->createMock(SourceRepositoryInterface::class);
        $object = $this->generateObjectPopulated(['sourceRepository' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit('sourceRepository', $form->setData(...))
        );
    }

    /**
     * @throws StateNotFound
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
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit('sourceRepository', $form->setData(...))
        );
    }

    public function testSetSourceRepositoryExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setSourceRepository(new stdClass());
    }

    public function testGetImagesRegistry()
    {
        $argument = $this->createMock(ImageRegistryInterface::class);
        $object = $this->generateObjectPopulated(['imagesRegistry' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit('imagesRegistry', $form->setData(...))
        );
    }

    /**
     * @throws StateNotFound
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
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit('imagesRegistry', $form->setData(...))
        );
    }

    public function testSetImagesRegistryExceptionOnBadArgument()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setImagesRegistry(new stdClass());
    }

    public function testGetClusters()
    {
        $argument = [new Cluster()];
        $object = $this->generateObjectPopulated(['clusters' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit('clusters', $form->setData(...))
        );
    }

    /**
     * @throws StateNotFound
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
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        self::assertInstanceOf(
            Project::class,
            $object->visit('clusters', $form->setData(...))
        );
    }

    public function testSetClustersExceptionOnBadArgument()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setClusters(new stdClass());
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
        $job->expects($this->once())->method('addToHistory')->with('foo', new DateTimeImmutable('2018-05-01'), true);

        self::assertInstanceOf(
            Project::class,
            $this->buildObject()->refuseExecution($job, 'foo', new DateTimeImmutable('2018-05-01'))
        );
    }

    public function testRefuseExecutionBadJob()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->refuseExecution(new stdClass(), 'foo', new DateTimeImmutable('2018-05-01'));
    }

    public function testRefuseExecutionBadError()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->refuseExecution($this->createMock(Job::class), new stdClass(), new DateTimeImmutable('2018-05-01'));
    }

    public function testRefuseExecutionBadDate()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->refuseExecution($this->createMock(Job::class), 'foo', new stdClass());
    }

    public function testPrepareJobProjectIsDraft()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);
        $account = $this->createMock(Account::class);

        $account->expects($this->never())->method('__call')->with('canIPrepareNewJob');
        $job->expects($this->once())->method('addToHistory')
            ->with('teknoo.east.paas.error.project.not_executable', $date = new DateTime('2018-05-01'), true);

        self::assertInstanceOf(
            Project::class,
            (new Project($account))
                ->prepareJob($job, $date, $env)
        );
    }

    public function testPrepareJobProjectIsDraftBadJob()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->prepareJob(new stdClass(), $this->createMock(Environment::class));
    }


    public function testPrepareJobProjectIsDraftBadDate()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->prepareJob($this->createMock(Job::class), $this->createMock(Environment::class), new stdClass());
    }

    public function testPrepareJobProjectIsExecutable()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);
        $account = $this->createMock(Account::class);

        $project = (new Project($account));

        $account->expects($this->once())->method('__call')->with('canIPrepareNewJob', [$project, $job, $date = new DateTime('2018-05-01'), $env]);
        $job->expects($this->never())->method('addToHistory');

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
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->prepareJob(new stdClass(), new DateTime('2018-05-01'), $this->createMock(Environment::class));
    }

    public function testPrepareJobProjectIsExecutableBadEnv()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->prepareJob($this->createMock(Job::class), new DateTime('2018-05-01'), new stdClass());
    }

    public function testPrepareJobProjectIsExecutableBadDate()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->prepareJob($this->createMock(Job::class), new stdClass(), $this->createMock(Environment::class));
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

        $cluster1->expects($this->once())->method('prepareJobForEnvironment')
            ->with($job, $env);
        $cluster2->expects($this->once())->method('prepareJobForEnvironment')
            ->with($job, $env);

        $project = $this->buildObject();
        $job->expects($this->atLeastOnce())
            ->method('setProject')
            ->with($project)
            ->willReturnSelf();

        $job->expects($this->atLeastOnce())
            ->method('setEnvironment')
            ->with($env)
            ->willReturnSelf();

        $job->expects($this->atLeastOnce())
            ->method('setSourceRepository')
            ->with($sourceRepository)
            ->willReturnSelf();

        $job->expects($this->atLeastOnce())
            ->method('setImagesRegistry')
            ->with($imagesRegistry)
            ->willReturnSelf();

        self::assertInstanceOf(
            Project::class,
            $project
                ->setSourceRepository($sourceRepository)
                ->setImagesRegistry($imagesRegistry)
                ->setClusters([$cluster1, $cluster2])
                ->configure($job, new DateTime('2018-05-01'), $env)
        );
    }

    public function testConfigureProjectIsExecutableWithQuota()
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);
        $sourceRepository = $this->createMock(SourceRepositoryInterface::class);
        $imagesRegistry = $this->createMock(ImageRegistryInterface::class);
        $cluster1 = $this->createMock(Cluster::class);
        $cluster2 = $this->createMock(Cluster::class);
        $quotas = ['compute' => ['cpu' => 5]];

        $cluster1->expects($this->once())->method('prepareJobForEnvironment')
            ->with($job, $env);
        $cluster2->expects($this->once())->method('prepareJobForEnvironment')
            ->with($job, $env);

        $project = $this->buildObject();
        $job->expects($this->atLeastOnce())
            ->method('setProject')
            ->with($project)
            ->willReturnSelf();

        $job->expects($this->atLeastOnce())
            ->method('setEnvironment')
            ->with($env)
            ->willReturnSelf();

        $job->expects($this->atLeastOnce())
            ->method('setSourceRepository')
            ->with($sourceRepository)
            ->willReturnSelf();

        $job->expects($this->atLeastOnce())
            ->method('setImagesRegistry')
            ->with($imagesRegistry)
            ->willReturnSelf();

        $job->expects($this->once())
            ->method('setQuotas')
            ->with($quotas)
            ->willReturnSelf();

        self::assertInstanceOf(
            Project::class,
            $project
                ->setSourceRepository($sourceRepository)
                ->setImagesRegistry($imagesRegistry)
                ->setClusters([$cluster1, $cluster2])
                ->configure($job, new DateTime('2018-05-01'), $env, $quotas)
        );
    }

    public function testConfigureProjectIsExecutableBadJob()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure(new stdClass(), new DateTime('2018-05-01'), $this->createMock(Environment::class));
    }

    public function testConfigureProjectIsExecutableBadEnv()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure($this->createMock(Job::class), new DateTime('2018-05-01'), new stdClass());
    }

    public function testConfigureProjectIsExecutableBadDate()
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure($this->createMock(Job::class), new stdClass(), $this->createMock(Environment::class));
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
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->listMeYourEnvironments(
            new stdClass()
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

    public function testExportToMeCrud()
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => Project::class,
                'id' => '123',
                'name' => 'fooName',
                'account' => $this->createMock(Account::class),
                'prefix' => '',
                'sourceRepository' => null,
                'imagesRegistry' => null,
                'clusters' => [],
            ]);

        self::assertInstanceOf(
            Project::class,
            $this->buildObject()->setId('123')->setName('fooName')->exportToMeData(
                $normalizer,
                ['groups' => 'crud']
            )
        );
    }

    public function testSetExportConfiguration()
    {
        Project::setExportConfiguration($conf = ['name' => ['default']]);
        $rc = new ReflectionClass(Project::class);

        self::assertEquals(
            $conf,
            $rc->getStaticPropertyValue('exportConfigurations'),
        );
    }
}
