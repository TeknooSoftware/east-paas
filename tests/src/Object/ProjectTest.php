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
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
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
use Teknoo\States\Exception\MethodNotImplemented;
use Teknoo\States\Proxy\Exception\StateNotFound;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use Throwable;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Executable::class)]
#[CoversClass(Draft::class)]
#[CoversClass(Project::class)]
class ProjectTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @throws StateNotFound
     */
    public function buildObject(): Project
    {
        return new Project($this->createMock(Account::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Project::setExportConfiguration(
            [
                '@class' => ['default', 'api', 'digest', 'crud'],
                'id' => ['default', 'api', 'digest', 'crud'],
                'account' => ['crud'],
                'name' => ['default', 'api', 'digest', 'crud'],
                'prefix' => ['crud'],
                'sourceRepository' => ['crud'],
                'imagesRegistry' => ['crud'],
                'clusters' => ['crud'],
            ]
        );
    }

    public function testConstructor(): void
    {
        $object = $this->buildObject();

        $rP = new ReflectionProperty($object, 'account');

        $this->assertInstanceOf(Account::class, $rP->getValue($object));
    }

    /**
     * @throws StateNotFound
     */
    public function testGetAccount(): void
    {
        $object = new Account();
        $this->assertEquals($object, $this->generateObjectPopulated(['account' => $object])->getAccount());
    }

    public function testGetAccountNoAccount(): void
    {
        $this->expectException(RuntimeException::class);
        new Project()->getAccount();
    }

    public function testGetName(): void
    {
        $argument = 'fooBar';
        $object = $this->generateObjectPopulated(['name' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('name', $form->setData(...)));
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

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('name', $form->setData(...)));
    }

    public function testSetNameExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setName(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testSetPrefix(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setPrefix('fooBar'));

        $argument = 'fooBar';

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('prefix', $form->setData(...)));
    }

    public function testSetPrefixExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setPrefix(new stdClass());
    }

    public function testGetSourceRepository(): void
    {
        $argument = $this->createMock(SourceRepositoryInterface::class);
        $object = $this->generateObjectPopulated(['sourceRepository' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('sourceRepository', $form->setData(...)));
    }

    /**
     * @throws StateNotFound
     */
    public function testSetSourceRepository(): void
    {
        $argument = $this->createMock(SourceRepositoryInterface::class);

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setSourceRepository($argument));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('sourceRepository', $form->setData(...)));
    }

    public function testSetSourceRepositoryExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setSourceRepository(new stdClass());
    }

    public function testGetImagesRegistry(): void
    {
        $argument = $this->createMock(ImageRegistryInterface::class);
        $object = $this->generateObjectPopulated(['imagesRegistry' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('imagesRegistry', $form->setData(...)));
    }

    /**
     * @throws StateNotFound
     */
    public function testSetImagesRegistry(): void
    {
        $argument = $this->createMock(ImageRegistryInterface::class);

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setImagesRegistry($argument));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('imagesRegistry', $form->setData(...)));
    }

    public function testSetImagesRegistryExceptionOnBadArgument(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->setImagesRegistry(new stdClass());
    }

    public function testGetClusters(): void
    {
        $argument = [new Cluster()];
        $object = $this->generateObjectPopulated(['clusters' => $argument]);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('clusters', $form->setData(...)));
    }

    /**
     * @throws StateNotFound
     */
    public function testSetClusters(): void
    {
        $object = $this->buildObject();
        $argument = [new Cluster()];
        $this->assertInstanceOf($object::class, $object->setClusters($argument));

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('setData')
            ->with($argument);

        $this->assertInstanceOf(Project::class, $object->visit('clusters', $form->setData(...)));
    }

    public function testSetClustersExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setClusters(new stdClass());
    }

    public function testUpdateClusters(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->updateClusters());
    }

    public function testRefuseExecution(): void
    {
        $job = $this->createMock(Job::class);
        $job->expects($this->once())->method('addToHistory')->with('foo', new DateTimeImmutable('2018-05-01'), true);

        $this->assertInstanceOf(Project::class, $this->buildObject()->refuseExecution($job, 'foo', new DateTimeImmutable('2018-05-01')));
    }

    public function testRefuseExecutionBadJob(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->refuseExecution(new stdClass(), 'foo', new DateTimeImmutable('2018-05-01'));
    }

    public function testRefuseExecutionBadError(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->refuseExecution($this->createMock(Job::class), new stdClass(), new DateTimeImmutable('2018-05-01'));
    }

    public function testRefuseExecutionBadDate(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()->refuseExecution($this->createMock(Job::class), 'foo', new stdClass());
    }

    public function testPrepareJobProjectIsDraft(): void
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);
        $account = $this->createMock(Account::class);

        $account->expects($this->never())->method('__call')->with('canIPrepareNewJob');
        $job->expects($this->once())->method('addToHistory')
            ->with('teknoo.east.paas.error.project.not_executable', $date = new DateTime('2018-05-01'), true);

        $this->assertInstanceOf(Project::class, new Project($account)
            ->prepareJob($job, $date, $env));
    }

    public function testPrepareJobProjectIsDraftBadJob(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->prepareJob(new stdClass(), $this->createMock(Environment::class));
    }


    public function testPrepareJobProjectIsDraftBadDate(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->prepareJob($this->createMock(Job::class), $this->createMock(Environment::class), new stdClass());
    }

    public function testPrepareJobProjectIsExecutable(): void
    {
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);
        $account = $this->createMock(Account::class);

        $project = (new Project($account));

        $account->expects($this->once())->method('__call')->with('canIPrepareNewJob', [$project, $job, $date = new DateTime('2018-05-01'), $env]);
        $job->expects($this->never())->method('addToHistory');

        $this->assertInstanceOf(Project::class, $project
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->prepareJob($job, $date, $env));
    }

    public function testPrepareJobProjectIsExecutableBadJob(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->prepareJob(new stdClass(), new DateTime('2018-05-01'), $this->createMock(Environment::class));
    }

    public function testPrepareJobProjectIsExecutableBadEnv(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->prepareJob($this->createMock(Job::class), new DateTime('2018-05-01'), new stdClass());
    }

    public function testPrepareJobProjectIsExecutableBadDate(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->prepareJob($this->createMock(Job::class), new stdClass(), $this->createMock(Environment::class));
    }

    public function testConfigureProjectIsDraft(): void
    {
        $this->expectException(MethodNotImplemented::class);
        $job = $this->createMock(Job::class);
        $env = $this->createMock(Environment::class);

        $this->buildObject()->configure($job, $env);
    }

    public function testConfigureProjectIsExecutable(): void
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

        $this->assertInstanceOf(Project::class, $project
            ->setSourceRepository($sourceRepository)
            ->setImagesRegistry($imagesRegistry)
            ->setClusters([$cluster1, $cluster2])
            ->configure($job, new DateTime('2018-05-01'), $env));
    }

    public function testConfigureProjectIsExecutableWithQuota(): void
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

        $this->assertInstanceOf(Project::class, $project
            ->setSourceRepository($sourceRepository)
            ->setImagesRegistry($imagesRegistry)
            ->setClusters([$cluster1, $cluster2])
            ->configure($job, new DateTime('2018-05-01'), $env, $quotas));
    }

    public function testConfigureProjectIsExecutableBadJob(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure(new stdClass(), new DateTime('2018-05-01'), $this->createMock(Environment::class));
    }

    public function testConfigureProjectIsExecutableBadEnv(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure($this->createMock(Job::class), new DateTime('2018-05-01'), new stdClass());
    }

    public function testConfigureProjectIsExecutableBadDate(): void
    {
        $this->expectException(TypeError::class);
        $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->configure($this->createMock(Job::class), new stdClass(), $this->createMock(Environment::class));
    }

    public function testListMeYourEnvironments(): void
    {
        $this->assertInstanceOf(Project::class, $this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->listMeYourEnvironments(
                static function (): void { }
            ));
    }

    public function testListMeYourEnvironmentsBadCallable(): void
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
                '@class' => Project::class,
                'id' => '123',
                'name' => 'fooName',
            ]);

        $this->assertInstanceOf(Project::class, $this->buildObject()->setId('123')->setName('fooName')->exportToMeData(
            $normalizer,
            ['foo' => 'bar']
        ));
    }

    public function testExportToMeCrud(): void
    {
        $project = $this->buildObject();

        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => Project::class,
                'id' => '123',
                'name' => 'fooName',
                'account' => $project->getAccount(),
                'prefix' => '',
                'sourceRepository' => null,
                'imagesRegistry' => null,
                'clusters' => [],
            ]);

        $this->assertInstanceOf(
            Project::class,
            $project
                ->setId('123')
                ->setName('fooName')
                ->exportToMeData(
                    $normalizer,
                    ['groups' => 'crud']
                )
        );
    }

    public function testSetExportConfiguration(): void
    {
        Project::setExportConfiguration($conf = ['name' => ['default']]);
        $rc = new ReflectionClass(Project::class);

        $this->assertEquals($conf, $rc->getStaticPropertyValue('exportConfigurations'));
    }

    public function testIsRunnable(): void
    {
        $this->assertFalse(new Project()->isRunnable());

        $this->assertTrue($this->buildObject()
            ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
            ->setClusters([$this->createMock(Cluster::class)])
            ->isRunnable());
    }
}
