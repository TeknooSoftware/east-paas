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

use ArrayObject;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Contracts\Object\ImageRegistryInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Job\Executing;
use Teknoo\East\Paas\Object\Job\Pending;
use Teknoo\East\Paas\Object\Job\Terminated;
use Teknoo\East\Paas\Object\Job\Validating;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\Proxy\Exception\MethodNotImplemented;
use Teknoo\States\Proxy\Exception\StateNotFound;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use Throwable;
use TypeError;

use function iterator_to_array;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Terminated::class)]
#[CoversClass(Validating::class)]
#[CoversClass(Pending::class)]
#[CoversClass(Executing::class)]
#[CoversClass(Job::class)]
class JobTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @throws StateNotFound
     */
    public function buildObject(): Job
    {
        return new Job();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Job::setExportConfiguration(
            [
                '@class' => ['default', 'api', 'digest'],
                'id' => ['default', 'api', 'digest'],
                'project' => ['default', 'api', 'digest'],
                'environment' => ['default', 'api', 'digest'],
                'prefix' => ['default', 'api'],
                'source_repository' => ['default', 'api'],
                'images_repository' => ['default', 'api'],
                'clusters' => ['default', 'api'],
                'history' => ['default', 'api'],
                'extra' => ['default', 'api'],
                'defaults' => ['default', 'api'],
                'quotas' => ['default', 'api'],
            ]
        );
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

    public function testGetProject(): void
    {
        $argument = new Project($this->createStub(Account::class));

        $object = $this->buildObject();
        $this->assertInstanceOf(Project::class, $object->setProject($argument)->getProject());
    }

    public function testSetProjectExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setProject(new stdClass());
    }

    public function testAddToHistory(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->addToHistory('foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']));

        $rP = new ReflectionProperty($object, 'history');
        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), $rP->getValue($object));

        $this->assertInstanceOf($object::class, $object->addToHistory('foo2', new DateTimeImmutable('2018-05-01')));

        $this->assertEquals(new History(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), 'foo2', new DateTimeImmutable('2018-05-01')), $rP->getValue($object));
    }

    public function testAddToHistoryExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->addToHistory(new stdClass());
    }

    public function testAddFromHistoryWithoutCallback(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->addFromHistory(
            new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo'])
        ));

        $rP = new ReflectionProperty($object, 'history');
        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), $rP->getValue($object));

        $this->assertInstanceOf($object::class, $object->addFromHistory(
            new History(null, 'foo2', new DateTimeImmutable('2018-05-01'))
        ));

        $this->assertEquals(new History(
            new History(
                null,
                'foo',
                new DateTimeImmutable('2018-05-01'),
                false,
                ['bar' => 'foo']
            ),
            'foo2',
            new DateTimeImmutable('2018-05-01')
        ), $rP->getValue($object));
    }

    public function testAddFromHistoryWithFinal(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->addFromHistory(
            new History(null, 'foo', new DateTimeImmutable('2018-05-01'), true, ['bar' => 'foo'])
        ));

        $rP = new ReflectionProperty($object, 'history');
        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), true, ['bar' => 'foo']), $rP->getValue($object));

        $this->assertInstanceOf($object::class, $object->addFromHistory(
            new History(null, 'foo2', new DateTimeImmutable('2018-05-01'))
        ));

        $this->assertEquals(new History(
            new History(
                null,
                'foo2',
                new DateTimeImmutable('2018-05-01'),
                false
            ),
            'foo',
            new DateTimeImmutable('2018-05-01'),
            true,
            ['bar' => 'foo'],
        ), $rP->getValue($object));
    }

    public function testAddFromHistoryWithCallback(): void
    {
        $object = $this->buildObject();
        $called = false;
        $callback = function (History $h) use (&$called): void {
            $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), $h);
            $called = true;
        };
        $this->assertInstanceOf($object::class, $object->addFromHistory(
            new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']),
            $callback
        ));
        $this->assertTrue($called);

        $rP = new ReflectionProperty($object, 'history');
        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), $rP->getValue($object));

        $called = false;
        $callback = function (History $h) use (&$called): void {
            $this->assertEquals(new History(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), 'foo2', new DateTimeImmutable('2018-05-01')), $h);
            $called = true;
        };
        $this->assertInstanceOf($object::class, $object->addFromHistory(
            new History(null, 'foo2', new DateTimeImmutable('2018-05-01')),
            $callback
        ));
        $this->assertTrue($called);

        $this->assertEquals(new History(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), 'foo2', new DateTimeImmutable('2018-05-01')), $rP->getValue($object));
    }

    public function testAddFromHistoryExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->addFromHistory(new stdClass());
    }

    public function testAddFromHistoryExceptionOnBadCallabled(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->addFromHistory($this->createStub(History::class), new stdClass());
    }

    public function testSetHistoryBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setHistory(new stdClass());
    }

    public function testSetHistory(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setHistory(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo'])));

        $rP = new ReflectionProperty($object, 'history');
        $this->assertEquals(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), $rP->getValue($object));
    }

    public function testGetHistory(): void
    {
        $object = $this->buildObject();
        $this->assertNotInstanceOf(\Teknoo\East\Paas\Object\History::class, $object->getHistory());

        $history = new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']);
        $this->assertEquals($history, $object->setHistory($history)->getHistory());
    }

    public function testSetExtra(): void
    {
        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setExtra(['foo' => 'bar']));

        $this->assertInstanceOf($object::class, $object->setExtra(['bar' => 'foo']));
    }

    public function testSetEnvironment(): void
    {
        $argument = new Environment('foo');

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setEnvironment($argument));
    }

    public function testSetEnvironmentsExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setEnvironment(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testSetSourceRepository(): void
    {
        $argument = $this->createStub(SourceRepositoryInterface::class);

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setSourceRepository($argument));
    }

    public function testSetSourceRepositoryExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setSourceRepository(new stdClass());
    }

    /**
     * @throws StateNotFound
     */
    public function testAddCluster(): void
    {
        $argument = $this->createStub(Cluster::class);

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->addCluster($argument));

        $this->assertInstanceOf($object::class, $object->addCluster($argument));
    }

    public function testSetClustersArray(): void
    {
        $argument = $this->createStub(Cluster::class);

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setClusters([$argument]));

        $rP = new ReflectionProperty($object, 'clusters');
        $this->assertEquals([$argument], $rP->getValue($object));
    }

    public function testSetClustersCollection(): void
    {
        $argument = $this->createStub(Cluster::class);

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setClusters(new ArrayObject([$argument])));

        $rP = new ReflectionProperty($object, 'clusters');
        $this->assertEquals(new ArrayObject([$argument]), $rP->getValue($object));
    }

    public function testGetCluster(): void
    {
        $argument = $this->createStub(Cluster::class);

        $object = $this->buildObject();
        $this->assertInstanceOf($object::class, $object->setClusters(new ArrayObject([$argument])));

        $this->assertInstanceOf(Job::class, $object->visit(
            'clusters',
            fn ($clusters) => $this->assertInstanceOf(Cluster::class, iterator_to_array($clusters)[0]),
        ));
    }

    public function testAddClusterExceptionOnBadArgument(): void
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->addCluster(new stdClass());
    }

    public function testHistory(): void
    {
        $this->assertInstanceOf(Job::class, new Job()
            ->setId('test')
            ->setProject(new Project(new Account()->setId('foo'))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($this->createStub(SourceRepositoryInterface::class))
            ->setImagesRegistry($this->createStub(ImageRegistryInterface::class))
            ->addCluster($this->createStub(Cluster::class))
            ->addToHistory('foo', new DateTimeImmutable('2018-05-01'), true));
    }

    public function testConfigureCloningAgentNotExecuting(): void
    {
        $this->expectException(MethodNotImplemented::class);
        $this->buildObject()->configureCloningAgent();
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
                '@class' => Job::class,
                'id' => '123',
                'project' => null,
                'environment' => null,
                'source_repository' => null,
                'images_repository' => null,
                'clusters' => [],
                'history' => null,
                'prefix' => 'bar',
                'extra' => ['foo' => 'bar', 'bar' => 'foo'],
                'defaults' => ['hello' => 'world'],
                'quotas' => [
                    'compute' => [
                        'cpu' => 4,
                    ],
                ]
            ]);

        $this->assertInstanceOf(Job::class, $this->buildObject()->setId('123')
            ->setPrefix('bar')
            ->setExtra(['foo' => 'bar'])
            ->setExtra(['bar' => 'foo'])
            ->setDefaults(['hello' => 'world'])
            ->setQuotas([
                'compute' => [
                    'cpu' => 4,
                ],
            ])
            ->exportToMeData(
                $normalizer,
                ['foo' => 'bar']
            ));
    }

    public function testExportToMeApi(): void
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => Job::class,
                'id' => '123',
                'project' => null,
                'environment' => null,
                'source_repository' => null,
                'images_repository' => null,
                'clusters' => [],
                'history' => null,
                'prefix' => 'bar',
                'extra' => ['foo' => 'bar', 'bar' => 'foo'],
                'defaults' => ['hello' => 'world'],
                'quotas' => [
                    'compute' => [
                        'cpu' => 4,
                    ],
                ]
            ]);

        $this->assertInstanceOf(Job::class, $this->buildObject()->setId('123')
            ->setPrefix('bar')
            ->setExtra(['foo' => 'bar'])
            ->setExtra(['bar' => 'foo'])
            ->setDefaults(['hello' => 'world'])
            ->setQuotas([
                'compute' => [
                    'cpu' => 4,
                ],
            ])
            ->exportToMeData(
                $normalizer,
                ['groups' => ['api']]
            ));
    }

    public function testExportToMeDigest(): void
    {
        $normalizer = $this->createMock(EastNormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('injectData')
            ->with([
                '@class' => Job::class,
                'id' => '123',
                'project' => null,
                'environment' => null,
            ]);

        $this->assertInstanceOf(Job::class, $this->buildObject()->setId('123')
            ->setPrefix('bar')
            ->setExtra(['foo' => 'bar'])
            ->setExtra(['bar' => 'foo'])
            ->exportToMeData(
                $normalizer,
                ['groups' => ['digest']]
            ));
    }

    public function testJobPendingIsRunnable(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(Job::class, new Job()->isRunnable($promise));
    }

    public function testJobTerminatedIsRunnable(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $object = new Job()
            ->setId('test')
            ->setProject(new Project(new Account()->setId('foo'))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($repo = $this->createStub(SourceRepositoryInterface::class))
            ->setImagesRegistry($repo = $this->createStub(ImageRegistryInterface::class))
            ->addCluster($this->createStub(Cluster::class))
            ->addToHistory('foo', new DateTimeImmutable('2018-05-01'), true);

        $this->assertInstanceOf(Job::class, $object->isRunnable($promise));
    }

    public function testJobTerminatedIsRunnableWithoutHistory(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $object = new Job()
            ->setId('test')
            ->setProject(new Project(new Account()->setId('foo'))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($repo = $this->createStub(SourceRepositoryInterface::class))
            ->setImagesRegistry($repo = $this->createStub(ImageRegistryInterface::class))
            ->addCluster($this->createStub(Cluster::class))
            ->addToHistory('foo', new DateTimeImmutable('2018-05-01'), true);

        $this->assertInstanceOf(Job::class, $object->isRunnable($promise));
    }

    public function testJobExecutingIsRunnable(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $object = new Job()
            ->setId('test')
            ->setProject(new Project(new Account()->setId('foo'))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($repo = $this->createStub(SourceRepositoryInterface::class))
            ->setImagesRegistry($repo = $this->createStub(ImageRegistryInterface::class))
            ->addCluster($this->createStub(Cluster::class))
            ->addToHistory('foo', new DateTimeImmutable('2018-05-01'));

        $this->assertInstanceOf(Job::class, $object->isRunnable($promise));
    }

    public function testJobPendingValidate(): void
    {
        $date = new DateTime('2018-01-01 00:00:00');
        $job = new Job();
        $this->assertInstanceOf(Job::class, $job->validate($date));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail')
            ->with(new RuntimeException('teknoo.east.paas.error.job.not_validated', 400));

        $this->assertInstanceOf(Job::class, $job->isRunnable($promise));
    }

    public function testJobExecutingValidate(): void
    {
        $date = new DateTime('2018-01-01 00:00:00');
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $object = new Job()
            ->setId('test')
            ->setProject(new Project(new Account()->setId('foo'))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($repo = $this->createStub(SourceRepositoryInterface::class))
            ->setImagesRegistry($repo = $this->createStub(ImageRegistryInterface::class))
            ->addCluster($this->createStub(Cluster::class));

        $this->assertInstanceOf(Job::class, $object->validate($date));
        $this->assertInstanceOf(Job::class, $object->isRunnable($promise));
    }

    public function testSetExportConfiguration(): void
    {
        Job::setExportConfiguration($conf = ['name' => ['default']]);
        $rc = new ReflectionClass(Job::class);

        $this->assertEquals($conf, $rc->getStaticPropertyValue('exportConfigurations'));
    }
}
