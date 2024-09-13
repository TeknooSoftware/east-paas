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

use ArrayObject;
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
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\Proxy\Exception\MethodNotImplemented;
use Teknoo\States\Proxy\Exception\StateNotFound;
use Teknoo\Tests\East\Common\Object\Traits\ObjectTestTrait;
use Throwable;
use TypeError;

use function iterator_to_array;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversTrait(ExportConfigurationsTrait::class)]
#[CoversClass(Terminated::class)]
#[CoversClass(Validating::class)]
#[CoversClass(Pending::class)]
#[CoversClass(Executing::class)]
#[CoversClass(Job::class)]
class JobTest extends TestCase
{
    use ObjectTestTrait;

    /**
     * @return Job
     * @throws StateNotFound
     */
    public function buildObject(): Job
    {
        return new Job();
    }

    public function testStatesListDeclaration()
    {
        $rf = new ReflectionMethod(Job::class, 'statesListDeclaration');
        $rf->setAccessible(true);
        self::assertIsArray($rf->getClosure()());
    }

    /**
     * @throws StateNotFound
     */
    public function testSetProject()
    {
        $argument = new Project($this->createMock(Account::class));

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setProject($argument)
        );

        $rP = new ReflectionProperty($object, 'project');
        $rP->setAccessible(true);
        self::assertEquals(
            $argument,
            $rP->getValue($object)
        );
    }

    public function testGetProject()
    {
        $argument = new Project($this->createMock(Account::class));

        $object = $this->buildObject();
        self::assertInstanceOf(
            Project::class,
            $object->setProject($argument)->getProject()
        );
    }

    public function testSetProjectExceptionOnBadArgument()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setProject(new stdClass());
    }

    public function testAddToHistory()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->addToHistory('foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo'])
        );

        $rP = new ReflectionProperty($object, 'history');
        $rP->setAccessible(true);
        self::assertEquals(
            new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']),
            $rP->getValue($object)
        );

        self::assertInstanceOf(
            $object::class,
            $object->addToHistory('foo2', new DateTimeImmutable('2018-05-01'))
        );

        self::assertEquals(
            new History(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), 'foo2', new DateTimeImmutable('2018-05-01')),
            $rP->getValue($object)
        );
    }

    public function testAddToHistoryExceptionOnBadArgument()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->addToHistory(new stdClass());
    }

    public function testAddFromHistoryWithoutCallback()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->addFromHistory(
                new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo'])
            )
        );

        $rP = new ReflectionProperty($object, 'history');
        $rP->setAccessible(true);
        self::assertEquals(
            new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']),
            $rP->getValue($object)
        );

        self::assertInstanceOf(
            $object::class,
            $object->addFromHistory(
                new History(null, 'foo2', new DateTimeImmutable('2018-05-01'))
            )
        );

        self::assertEquals(
            new History(
                new History(
                    null,
                    'foo',
                    new DateTimeImmutable('2018-05-01'),
                    false,
                    ['bar' => 'foo']
                ),
                'foo2',
                new DateTimeImmutable('2018-05-01')
            ),
            $rP->getValue($object)
        );
    }

    public function testAddFromHistoryWithFinal()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->addFromHistory(
                new History(null, 'foo', new DateTimeImmutable('2018-05-01'), true, ['bar' => 'foo'])
            )
        );

        $rP = new ReflectionProperty($object, 'history');
        $rP->setAccessible(true);
        self::assertEquals(
            new History(null, 'foo', new DateTimeImmutable('2018-05-01'), true, ['bar' => 'foo']),
            $rP->getValue($object)
        );

        self::assertInstanceOf(
            $object::class,
            $object->addFromHistory(
                new History(null, 'foo2', new DateTimeImmutable('2018-05-01'))
            )
        );

        self::assertEquals(
            new History(
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
            ),
            $rP->getValue($object)
        );
    }

    public function testAddFromHistoryWithCallback()
    {
        $object = $this->buildObject();
        $called = false;
        $callback = function (History $h) use (&$called) {
            self::assertEquals(
                new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']),
                $h
            );
            $called = true;
        };
        self::assertInstanceOf(
            $object::class,
            $object->addFromHistory(
                new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']),
                $callback
            )
        );
        self::assertTrue($called);

        $rP = new ReflectionProperty($object, 'history');
        $rP->setAccessible(true);
        self::assertEquals(
            new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']),
            $rP->getValue($object)
        );

        $called = false;
        $callback = function (History $h) use (&$called) {
            self::assertEquals(
                new History(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), 'foo2', new DateTimeImmutable('2018-05-01')),
                $h
            );
            $called = true;
        };
        self::assertInstanceOf(
            $object::class,
            $object->addFromHistory(
                new History(null, 'foo2', new DateTimeImmutable('2018-05-01')),
                $callback
            )
        );
        self::assertTrue($called);

        self::assertEquals(
            new History(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']), 'foo2', new DateTimeImmutable('2018-05-01')),
            $rP->getValue($object)
        );
    }

    public function testAddFromHistoryExceptionOnBadArgument()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->addFromHistory(new stdClass());
    }

    public function testAddFromHistoryExceptionOnBadCallabled()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->addFromHistory($this->createMock(History::class), new stdClass());
    }

    public function testSetHistoryBadArgument()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setHistory(new stdClass());
    }

    public function testSetHistory()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setHistory(new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']))
        );

        $rP = new ReflectionProperty($object, 'history');
        $rP->setAccessible(true);
        self::assertEquals(
            new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']),
            $rP->getValue($object)
        );
    }

    public function testGetHistory()
    {
        $object = $this->buildObject();
        self::assertNull(
            $object->getHistory()
        );

        $history = new History(null, 'foo', new DateTimeImmutable('2018-05-01'), false, ['bar' => 'foo']);
        self::assertEquals(
            $history,
            $object->setHistory($history)->getHistory()
        );
    }

    public function testSetExtra()
    {
        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setExtra(['foo' => 'bar'])
        );

        self::assertInstanceOf(
            $object::class,
            $object->setExtra(['bar' => 'foo'])
        );
    }

    public function testSetEnvironment()
    {
        $argument = new Environment('foo');

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setEnvironment($argument)
        );
    }

    public function testSetEnvironmentsExceptionOnBadArgument()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setEnvironment(new stdClass());
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
    }

    public function testSetSourceRepositoryExceptionOnBadArgument()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->setSourceRepository(new stdClass());
    }
    
    /**
     * @throws StateNotFound
     */
    public function testAddCluster()
    {
        $argument = $this->createMock(Cluster::class);

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->addCluster($argument)
        );

        self::assertInstanceOf(
            $object::class,
            $object->addCluster($argument)
        );
    }

    public function testSetClustersArray()
    {
        $argument = $this->createMock(Cluster::class);

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setClusters([$argument])
        );

        $rP = new ReflectionProperty($object, 'clusters');
        $rP->setAccessible(true);
        self::assertEquals(
            [$argument],
            $rP->getValue($object)
        );
    }

    public function testSetClustersCollection()
    {
        $argument = $this->createMock(Cluster::class);

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setClusters(new ArrayObject([$argument]))
        );

        $rP = new ReflectionProperty($object, 'clusters');
        $rP->setAccessible(true);
        self::assertEquals(
            new ArrayObject([$argument]),
            $rP->getValue($object)
        );
    }

    public function testGetCluster()
    {
        $argument = $this->createMock(Cluster::class);

        $object = $this->buildObject();
        self::assertInstanceOf(
            $object::class,
            $object->setClusters(new ArrayObject([$argument]))
        );

        self::assertInstanceOf(
            Job::class,
            $object->visit(
                'clusters',
                fn ($clusters) => self::assertInstanceOf(
                    Cluster::class,
                    iterator_to_array($clusters)[0],
                ),
            ),
        );
    }

    public function testAddClusterExceptionOnBadArgument()
    {
        $this->expectException(Throwable::class);
        $this->buildObject()->addCluster(new stdClass());
    }

    public function testHistory()
    {
        self::assertInstanceOf(
            Job::class,
            (new Job())
                ->setId('test')
                ->setProject((new Project((new Account())->setId('foo')))->setId('bar')->setName('hello'))
                ->setEnvironment(new Environment('foo'))
                ->setSourceRepository($this->createMock(SourceRepositoryInterface::class))
                ->setImagesRegistry($this->createMock(ImageRegistryInterface::class))
                ->addCluster($this->createMock(Cluster::class))
                ->addToHistory('foo', new DateTimeImmutable('2018-05-01'), true)
        );
    }

    public function testConfigureCloningAgentNotExecuting()
    {
        $this->expectException(MethodNotImplemented::class);
        $this->buildObject()->configureCloningAgent();
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

        self::assertInstanceOf(
            Job::class,
            $this->buildObject()->setId('123')
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
            )
        );
    }

    public function testExportToMeApi()
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

        self::assertInstanceOf(
            Job::class,
            $this->buildObject()->setId('123')
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
            )
        );
    }

    public function testExportToMeDigest()
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

        self::assertInstanceOf(
            Job::class,
            $this->buildObject()->setId('123')
                ->setPrefix('bar')
                ->setExtra(['foo' => 'bar'])
                ->setExtra(['bar' => 'foo'])
                ->exportToMeData(
                $normalizer,
                ['groups' => ['digest']]
            )
        );
    }

    public function testJobPendingIsRunnable()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(Job::class, (new Job())->isRunnable($promise));
    }

    public function testJobTerminatedIsRunnable()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $object = (new Job())
            ->setId('test')
            ->setProject((new Project((new Account())->setId('foo')))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($repo = $this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($repo = $this->createMock(ImageRegistryInterface::class))
            ->addCluster($this->createMock(Cluster::class))
            ->addToHistory('foo', new DateTimeImmutable('2018-05-01'), true);

        self::assertInstanceOf(Job::class, $object->isRunnable($promise));
    }

    public function testJobTerminatedIsRunnableWithoutHistory()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $object = (new Job())
            ->setId('test')
            ->setProject((new Project((new Account())->setId('foo')))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($repo = $this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($repo = $this->createMock(ImageRegistryInterface::class))
            ->addCluster($this->createMock(Cluster::class))
            ->addToHistory('foo', new DateTimeImmutable('2018-05-01'), true);

        self::assertInstanceOf(Job::class, $object->isRunnable($promise));
    }

    public function testJobExecutingIsRunnable()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $object = (new Job())
            ->setId('test')
            ->setProject((new Project((new Account())->setId('foo')))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($repo = $this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($repo = $this->createMock(ImageRegistryInterface::class))
            ->addCluster($this->createMock(Cluster::class))
            ->addToHistory('foo', new DateTimeImmutable('2018-05-01'));

        self::assertInstanceOf(Job::class, $object->isRunnable($promise));
    }

    public function testJobPendingValidate()
    {
        $date = new DateTime('2018-01-01 00:00:00');
        $job = new Job();
        self::assertInstanceOf(Job::class, $job->validate($date));

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail')
            ->with(new RuntimeException('teknoo.east.paas.error.job.not_validated', 400));

        self::assertInstanceOf(Job::class, $job->isRunnable($promise));
    }

    public function testJobExecutingValidate()
    {
        $date = new DateTime('2018-01-01 00:00:00');
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $object = (new Job())
            ->setId('test')
            ->setProject((new Project((new Account())->setId('foo')))->setId('bar')->setName('hello'))
            ->setEnvironment(new Environment('foo'))
            ->setSourceRepository($repo = $this->createMock(SourceRepositoryInterface::class))
            ->setImagesRegistry($repo = $this->createMock(ImageRegistryInterface::class))
            ->addCluster($this->createMock(Cluster::class));

        self::assertInstanceOf(Job::class, $object->validate($date));
        self::assertInstanceOf(Job::class, $object->isRunnable($promise));
    }

    public function testSetExportConfiguration()
    {
        Job::setExportConfiguration($conf = ['name' => ['default']]);
        $rc = new ReflectionClass(Job::class);

        self::assertEquals(
            $conf,
            $rc->getStaticPropertyValue('exportConfigurations'),
        );
    }
}
