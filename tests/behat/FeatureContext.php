<?php

declare(strict_types=1);

namespace Teknoo\Tests\East\Paas\Behat;

use Behat\Behat\Context\Context;
use DateTime;
use DI\Container as DiContainer;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;
use Maclof\Kubernetes\Client;
use Maclof\Kubernetes\Models\Model;
use Maclof\Kubernetes\Repositories\Repository;
use Maclof\Kubernetes\RepositoryRegistry;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as SfContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Teknoo\DI\SymfonyBridge\DIBridgeBundle;
use Teknoo\East\CommonBundle\TeknooEastCommonBundle;
use Teknoo\East\FoundationBundle\EastFoundationBundle;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Common\Object\User;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Account;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Infrastructures\EastPaasBundle\TeknooEastPaasBundle;
use Teknoo\East\Paas\Infrastructures\Image\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\ImageRegistry;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job as OriJob;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
use Teknoo\East\Paas\Object\Project as OriProject;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Project;
use Teknoo\East\Common\Service\DatesService;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Symfony\Component\DependencyInjection\Container;
use Throwable;
use Traversable;

use function dirname;
use function json_decode;
use function json_encode;
use function random_int;
use function strlen;
use function var_export;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Container
     */
    private $sfContainer;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var DocumentRepository
     */
    private $repositories = [];

    /**
     * @var array
     */
    static private $objects = [];

    /** ARGUMENTS */

    /**
     * @var string
     */
    private $accountId;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var string
     */
    private $projectName;

    /**
     * @var string
     */
    private $projectId;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $jobId;

    /**
     * @var string
     */
    private $jobDate;

    /**
     * @var Job
     */
    private $job;

    /**
     * @var string
     */
    private $calledUrl;

    /**
     * @var string
     */
    private $clusterName;

    /**
     * @var Cluster
     */
    private $cluster;

    private string $clusterType = 'behat';

    /**
     * @var string
     */
    private $envName;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $repositoryUrl;

    /**
     * @var GitRepository
     */
    private $sourceRepository;

    /**
     * @var ImageRegistry
     */
    private $imagesRegistry;

    /**
     * @var string
     */
    private $historyMessage;

    /**
     * @var string
     */
    private $historyDate;

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    private $response;

    /**
     * @var string
     */
    private $requestBody;

    private static $useHnc = false;

    private array $manifests = [];

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yaml.
     *
     * @param KernelInterface $kernel
     * @param Container $container
     */
    public function __construct()
    {
        include_once __DIR__ . '/../../tests/fakeQuery.php';
    }

    /**
     * @Given the platform is booted
     */
    public function thePlatformIsBooted()
    {
        $this->initiateSymfonyKernel();
        $this->sfContainer->set(ObjectManager::class, $this->buildObjectManager());
        $this->sfContainer->get(DatesService::class)
            ->setCurrentDate(new DateTime('2018-10-01 02:03:04', new \DateTimeZone('UTC')));
        $this->sfContainer
            ->get(SerialGenerator::class)
            ->setGenerator(fn() => 0);
    }

    private function initiateSymfonyKernel()
    {
        $this->kernel = new class($this, 'test') extends BaseKernel
        {
            use MicroKernelTrait;

            private FeatureContext $context;

            public function __construct(FeatureContext $context, $environment)
            {
                $this->context = $context;

                parent::__construct($environment, false);
            }

            public function getProjectDir(): string
            {
                return dirname(__DIR__, 2);
            }

            public function getCacheDir(): string
            {
                return dirname(__DIR__, 1).'/var/cache';
            }

            public function getLogDir(): string
            {
                return dirname(__DIR__, 1).'/var/logs';
            }

            public function registerBundles(): iterable
            {
                yield new FrameworkBundle();
                yield new EastFoundationBundle();
                yield new TeknooEastCommonBundle();
                yield new TeknooEastPaasBundle();
                yield new DIBridgeBundle();
                yield new SecurityBundle();
            }

            protected function configureContainer(SfContainerBuilder $container, LoaderInterface $loader)
            {
                $confDir = __DIR__ . '/config';
                $configExts = '.{php,xml,yaml,yml}';
                $loader->load($confDir . '/{packages}/*' . $configExts, 'glob');
                $loader->load($confDir . '/{services}' . $configExts, 'glob');
                $loader->load(__DIR__.'/config/services.yaml');
                $container->setParameter('container.autowiring.strict_mode', true);
                $container->setParameter('container.dumper.inline_class_loader', true);

                $container->set(ObjectManager::class, $this->context->buildObjectManager());
            }

            protected function configureRoutes($routes)
            {
                if ($routes instanceof RoutingConfigurator) {
                    $routes->import(__DIR__ . '/config/routes/*.yaml', 'glob');
                } else {
                    $routes->import(__DIR__ . '/config/routes/*.yaml', '', 'glob');
                }
            }

            protected function getContainerClass(): string
            {
                $characters = 'abcdefghijklmnopqrstuvwxyz';
                $str = '';
                for ($i = 0; $i < 10; $i++) {
                    $str .= $characters[random_int(0, strlen($characters) - 1)];
                }

                return $str;
            }
        };

        $this->kernel->boot();
        $this->sfContainer = $this->kernel->getContainer();
    }

    /**
     * @BeforeScenario
     */
    public function clearData()
    {
        $this->objectManager = null;
        $this->repositories = [];
        self::$objects = [];
        $this->accountId = null;
        $this->account = null;
        $this->projectName = null;
        $this->projectId = null;
        $this->project = null;
        $this->calledUrl = null;
        $this->clusterName = null;
        $this->envName = null;
        $this->repositoryUrl = null;
        $this->response = null;
    }

    public function getRepository(string $className)
    {
        if (!isset($this->repositories[$className])) {
            throw new RuntimeException("Missing $className");
        }

        return $this->repositories[$className];
    }

    public function getObject(string $className, string $id)
    {
        if (isset(self::$objects[$className][$id])) {
            return self::$objects[$className][$id];
        }

        return null;
    }

    public function setObject(string $className, string $id, $object)
    {
        self::$objects[$className][$id] = $object;
    }

    public function buildObjectManager()
    {
        $this->objectManager = new class([$this, 'getRepository']) implements ObjectManager {
            private $repositories;

            public function __construct(callable $repositories){
                $this->repositories = $repositories;
            }

            public function find($className, $id) {}
            public function persist($object) {}
            public function remove($object) {}
            public function merge($object) {}
            public function clear($objectName = null) {}
            public function detach($object) {}
            public function refresh($object) {}
            public function flush() {}
            public function getRepository($className) {
                return ($this->repositories)($className);
            }
            public function getClassMetadata($className) {}
            public function getMetadataFactory() {}
            public function initializeObject($obj) {}
            public function contains($object) {}
        };

        return $this->objectManager;
    }

    private function buildRepository(string $className)
    {
        $this->repositories[$className] = new class($className, [$this, 'getObject'], [$this, 'setObject'])
            extends DocumentRepository {
            private $className;
            private $getter;
            private $setter;

            public function __construct($className, callable $getter, callable $setter)
            {
                $this->className = $className;
                $this->getter = $getter;
                $this->setter = $setter;
            }

            public function register(string $id, $object): self {
                ($this->setter)($this->className, $id, $object);

                return $this;
            }

            public function findOneBy(array $criteria, ?array $sort = null): ?object {
                $id = $criteria['id'];
                return ($this->getter)($this->className, $id);
            }

            public function getClassName(): string {
                return $this->className;
            }

            public function createQueryBuilder(): QueryBuilder
            {
                $qb = new class($this->getter, $this->className) extends QueryBuilder {
                    private array $criteria;

                    /**
                     * @var callable
                     */
                    private $getter;

                    public function __construct(
                        callable $getter,
                        private string $className,
                    ) {
                        $this->getter = $getter;
                    }

                    public function equals($value): QueryBuilder
                    {
                        $this->criteria = $value;

                        return $this;
                    }

                    public function prime($primer = true): QueryBuilder
                    {
                        return $this;
                    }

                    public function getQuery(array $options = []): Query
                    {
                        $query = new Query();

                        $id = $this->criteria['id'] ?? $this->criteria['_id'];
                        $query->resultToReturn = ($this->getter)($this->className, $id);


                        return $query;
                    }
                };

                return $qb;
            }
        };

        return $this->repositories[$className];
    }

    /**
     * @Given I have a configured platform
     */
    public function iHaveAConfiguredPlatform()
    {
        $this->clusterType = 'behat';

        $this->buildRepository(Account::class);
        $this->buildRepository(Cluster::class);
        $this->buildRepository(Job::class);
        $this->buildRepository(Project::class);
        $this->buildRepository(User::class);

        self::$useHnc = false;
    }

    /**
     * @Given A consumer Account :id
     */
    public function aConsumerAccount($id)
    {
        $this->accountId = $id;
        $this->repositories[Account::class]->register(
            $id,
            $this->account = (new Account())->setId($this->accountId)
                ->setName('Consumer Account')
                ->setNamespace('behat-test')
                ->setUseHierarchicalNamespaces(self::$useHnc)
        );
    }

    /**
     * @Given a project on this account :name with the id :id
     */
    public function aProjectOnThisAccountWithTheId($name, $id)
    {
        $this->projectName = $name;
        $this->projectId = $id;

        $this->repositories[Project::class]->register(
            $id,
            $this->project = (new Project($this->account))->setId($this->projectId)->setName($this->projectName)
        );
    }

    /**
     * @Given a cluster :name dedicated to the environment :id
     */
    public function aClusterDedicatedToTheEnvironment($name, $id)
    {
        $this->clusterName = $name;
        $this->envName = $id;

        $this->project->setClusters([
            $this->cluster = (new Cluster())->setId('cluster-id')->setType($this->clusterType)->setProject($this->project)
                ->setName($this->clusterName)->setEnvironment($this->environment = new Environment($this->envName))
        ]);
    }

    /**
     * @Given a repository on the url :url
     */
    public function aRepositoryOnTheUrl($url)
    {
        $this->repositoryUrl = $url;

        $this->project->setSourceRepository(
            $this->sourceRepository = (new GitRepository($this->repositoryUrl))->setId('git-id')
        );
    }

    /**
     * @Given a doctrine repository
     */
    public function aDoctrineRepository()
    {
        $this->project->setImagesRegistry(
            $this->imagesRegistry = (new ImageRegistry(
                'https://foo.bar'
            ))
        );
    }

    /**
     * @When I call the PaaS with this PUT request :url
     */
    public function iCallThePaasWithThisPutRequest($url)
    {
        $this->calledUrl = $url;

        $request = Request::create('https://'.$this->sfContainer->getParameter('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], [], $this->requestBody);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @When I call the PaaS with this PUT request :url with body :body and content type defined to :contentType
     */
    public function iCallThePaasWithThisPutRequestWithBodyAndContentTypeDefinedTo($url, $body, $contentType)
    {
        $this->calledUrl = $url;
        $this->requestBody = $body;

        $request = Request::create('https://'.$this->sfContainer->getParameter('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], ['CONTENT_TYPE' => $contentType], $this->requestBody);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @When I push a new message :text at :date to :url
     */
    public function iPushANewMessageAtTo($text, $date, $url)
    {
        $this->calledUrl = $url;

        $body = json_encode(new History(null, $this->historyMessage = $text, new DateTime($this->historyDate = $date)));
        $request = Request::create('https://'.$this->sfContainer->getParameter('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], [], $body);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @When I run a job :jobId from project :projectId to :url
     */
    public function iRunANewJobFromProjectAtTo($jobId, $projectId, $url)
    {
        $this->calledUrl = $url;
        $hnc = (int) self::$useHnc;
        $clusterType = $this->clusterType;

        $body = <<<EOF
{
  "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\Job",
  "id": "$jobId",
  "hierarchical_namespaces": $hnc,
  "project": {
    "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\Project",
    "id": "$projectId",
    "name": "Test"
  },
  "environment": {
    "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\Environment",
    "name": "staging"
  },
  "source_repository": {
    "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\GitRepository",
    "id": "42c6351ad59a37409cc1192d57287437",
    "pull_url": "fooBar",
    "default_branch": "main",
    "identity": {
      "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\SshIdentity",
      "id": "df02b84131663568b62750e4d6b71922",
      "private_key": "fooBar"
    }
  },
  "images_repository": {
    "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\ImageRegistry",
    "id": "530651c2cd6937158eaf11d36b8eeed4",
    "api_url": "fooBar",
    "identity": {
      "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\XRegistryAuth",
      "id": "d0d5605e129039778465f5279c16fa29",
      "username": "fooBar",
      "password": "fooBar",
      "email": "fooBar",
      "auth": "",
      "server_address": "fooBar"
    }
  },
  "clusters": [
    {
      "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\Cluster",
      "id": "4f719ead65683a1986339be59bbb03ab",
      "name": "fooBar",
      "address": "fooBar",
      "type": "$clusterType",
      "identity": {
        "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\ClusterCredentials",
        "id": "f61d411e3f1b33eaa0900d3b17d36f1d",
        "server_certificate": "fooBar",
        "token": "fooBar"
      },
      "environment": {
        "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\Environment",
        "name": "staging"
      }
    }
  ],
  "history": {
    "message": "teknoo.east.paas.jobs.configured",
    "date": "2020-08-26 09:13:55 UTC",
    "is_final": false,
    "extra": [],
    "previous": null
  },
  "extra": {
    "foo": "bar"
  },
  "variables": {
    "FOO": "foo"
  }
}
EOF;

        $request = Request::create('https://'.$this->sfContainer->getParameter('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], [], $body);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @Then I must obtain an HTTP answer with this status code equals to :code.
     */
    public function iMustObtainAnHttpAnswerWithThisStatusCodeEqualsTo($code)
    {
        Assert::assertInstanceOf(Response::class, $this->response);
        Assert::assertEquals($code, $this->response->getStatusCode(), $this->response->getContent());
    }

    /**
     * @Then with this body answer, the problem json, :body
     */
    public function withThisBodyAnswerTheProblemJson($body)
    {
        Assert::assertEquals('application/problem+json', $this->response->headers->get('Content-Type'));
        $expected = json_decode($body, true);
        $actual = json_decode($current = $this->response->getContent(), true);
        Assert::assertEquals($expected, $actual);
    }

    private function getNormalizedJob(array $variables = [], bool $hnc = false): array
    {
        return [
            '@class' => OriJob::class,
            'id' => '',
            'project' => [
                '@class' => OriProject::class,
                'id' => $this->projectId,
                'name' => $this->projectName,
            ],
            'base_namespace' => 'behat-test',
            'hierarchical_namespaces' => $hnc,
            'environment' => [
                '@class' => Environment::class,
                'name' => $this->envName,
            ],
            'source_repository' => [
                '@class' => GitRepository::class,
                'id' => 'git-id',
                'pull_url' => $this->repositoryUrl,
                'default_branch' => 'main',
                'identity' => null,
            ],
            'images_repository' => [
                '@class' => ImageRegistry::class,
                'id' => '',
                'api_url' => 'https://foo.bar',
                'identity' => null,
            ],
            'clusters' => [
                [
                    '@class' => Cluster::class,
                    'id' => 'cluster-id',
                    'name' => $this->clusterName,
                    'type' => $this->clusterType,
                    'address' => '',
                    'identity' => null,
                    'environment' => [
                        '@class' => Environment::class,
                        'name' => $this->envName,
                    ],
                ],
            ],
            'history' => [
                'message' => 'teknoo.east.paas.jobs.configured',
                'date' => '2018-10-01 02:03:04 UTC',
                'is_final' => false,
                'extra' => [],
                'previous' => null,
                'serial_number' => 0,
            ],
            'extra' => [],
            'variables' => $variables,
        ];
    }

    /**
     * @Then with the job normalized in the body.
     */
    public function withTheJobNormalizedInTheBody()
    {
        $job = $this->getNormalizedJob([]);

        $content = json_decode($this->response->getContent(), true);
        try {
            Assert::assertEquals(
                $job,
                $content
            );
        } catch (ExpectationFailedException $error) {
            throw new RuntimeException((string) $error, $error->getCode(), $error);
        }
    }

    /**
     * @Then with the job normalized in the body with variables :variables
     */
    public function withTheJobNormalizedInTheBodyWithVariables($variables)
    {
        $job = $this->getNormalizedJob(json_decode($variables, true));

        $content = json_decode($this->response->getContent(), true);

        try {
            Assert::assertEquals($job, $content);
        } catch (ExpectationFailedException $error) {
            throw new RuntimeException((string) $error, $error->getCode(), $error);
        }
    }

    /**
     * @Then with the job normalized with hnc in the body.
     */
    public function withTheJobNormalizedWithHncInTheBody()
    {
        $job = $this->getNormalizedJob([], true);

        $content = json_decode($this->response->getContent(), true);
        try {
            Assert::assertEquals(
                $job,
                $content
            );
        } catch (ExpectationFailedException $error) {
            throw new RuntimeException((string) $error, $error->getCode(), $error);
        }
    }

    /**
     * @Then with the job normalized with hnc in the body with variables :variables
     */
    public function withTheJobNormalizedWithHncInTheBodyWithVariables($variables)
    {
        $job = $this->getNormalizedJob(json_decode($variables, true), true);

        $content = json_decode($this->response->getContent(), true);

        try {
            Assert::assertEquals($job, $content);
        } catch (ExpectationFailedException $error) {
            throw new RuntimeException((string) $error, $error->getCode(), $error);
        }
    }

    private function setAJobWithTheIdAtDate(mixed $id, string $date, bool $hnc)
    {
        $this->jobId = $id;
        $this->jobDate = $date;

        $this->job = (new Job())->setId($this->jobId)
            ->setProject($this->project)
            ->setSourceRepository($this->sourceRepository)
            ->setClusters([$this->cluster])
            ->setEnvironment($this->environment)
            ->setExtra(['foo' => 'bar'])
            ->useHierarchicalNamespaces($hnc)
            ->addToHistory('teknoo.east.paas.jobs.configured', new DateTime($this->jobDate));

        $this->repositories[Job::class]->register(
            $id,
            $this->job
        );
    }

    /**
     * @Given a job with the id :id at date :date
     */
    public function aJobWithTheIdAtDate($id, $date)
    {
        $this->setAJobWithTheIdAtDate($id, $date, false);
    }

    /**
     * @Given a job with the id :id at date :date and HNC
     */
    public function aJobWithTheIdAtDateAndHnc($id, $date)
    {
        $this->setAJobWithTheIdAtDate($id, $date, true);
    }

    /**
     * @Then with the history :message at date :date normalized in the body.
     */
    public function withTheHistoryAtDateNormalizedInTheBody($message, $date)
    {
        $history = [
            'message' => $this->historyMessage = $message,
            'date' => $this->historyDate = $date,
            'is_final' => false,
            'extra' => [],
            'previous' => new History(
                null, 'teknoo.east.paas.jobs.configured',
                new DateTime($this->jobDate)
            ),
           'serial_number' => 0,
        ];
        Assert::assertEquals(
            json_encode($history),
            $this->response->getContent()
        );
    }

    /**
     * @Then with the final history at date :date in the body
     */
    public function withTheFinalHistoryInTheBody($date)
    {
        $history = [
            'message' => DispatchResultInterface::class,
            'date' => $this->historyDate = $date,
            'is_final' => true,
            'extra' => [
                'foo' => 'bar',
                'result' => [],
            ],
            'previous' => null,
            'serial_number' => 0,
        ];

        $content = json_decode($this->response->getContent(), true);

        Assert::assertEquals($history, $content);
    }

    /**
     * @Given a malformed body
     */
    public function aMalformedBody()
    {
        $this->requestBody = 'fooBar';
    }

    /**
     * @Given a job workspace agent
     */
    public function aJobWorkspaceAgent()
    {
        $workspace = new class implements JobWorkspaceInterface {
            use ImmutableTrait;

            public function setJob(JobUnitInterface $job): JobWorkspaceInterface
            {
                return $this;
            }

            public function clean(): JobWorkspaceInterface
            {
                return $this;
            }

            public function writeFile(FileInterface $file, callable $return = null): JobWorkspaceInterface
            {
                return $this;
            }

            public function prepareRepository(CloningAgentInterface $cloningAgent): JobWorkspaceInterface
            {
                return $this;
            }

            public function loadDeploymentIntoConductor(
                ConductorInterface $conductor,
                PromiseInterface $promise
            ): JobWorkspaceInterface {
                $conf = <<<'EOF'
paas: #Dedicated to compiler
  version: v1

#Config
maps:
  map1:
    key1: value1
    key2: ${FOO}
  map2:
    foo: bar
    bar: foo

#Secrets provider
secrets:
  map-vault:
    provider: map #Internal secrets, must be passed in this file
    options:
      key1: value1
      key2: ${FOO}
  map-vault2:
    provider: map #Internal secrets, must be passed in this file
    options:
      hello: world
  volume-vault:
    provider: map
    type: foo
    options:
      foo: bar
      bar: foo

#Custom image, not available in the library
images:
  foo:
    build-name: foo
    tag: latest
    path: '/images/${FOO}'

#Hook to build the project before container, Called in this order
builds:  
  composer-build: #Name of the step
    composer: 
      action: install #Hook to call
      arguments:
        - 'no-dev'
        - 'optimize-autoloader'
        - 'classmap-authoritative'
  custom-hook:
    hook-id: foo bar

#Volume to build to use with container
volumes:
  extra: #Name of the volume
    local-path: "/foo/bar" #optional local path where store data in the volume
    add: #folder or file, from .paas.yaml where is located to add to the volume
      - 'extra'
  other_name: #Name of the volume
    add: #folder or file, from .paas.yaml where is located to add to the volume
      - 'vendor'

#Pods (set of container)
pods:
  php-pods: #podset name
    replicas: 2 #instance of pods
    containers:
      php-run: #Container name
        image: registry.teknoo.software/php-run #Container image to use
        version: 7.4
        listen: #Port listen by the container
          - 8080
        volumes: #Volumes to link
          extra:
            from: 'extra'
            mount-path: '/opt/extra' #Path where volume will be mount
          app:
            mount-path: '/opt/app' #Path where data will be stored
            add: #folder or file, from .paas.yaml where is located to add to the volume
              - 'src'
              - 'vendor'
              - 'composer.json'
              - 'composer.lock'
              - 'composer.phar'
          data: #Persistent volume, can not be pre-populated
            mount-path: '/opt/data'
            persistent: true
            storage-size: 3Gi
          map:
            mount-path: '/map'
            from-map: 'map2'
          vault:
            mount-path: '/vault'
            from-secret: 'volume-vault'
        variables: #To define some environment variables
          SERVER_SCRIPT: '/opt/app/src/server.php'
          from-maps:
            KEY0: 'map1.key0'
          import-maps:
            - 'map2'
          from-secrets: #To fetch some value from secret/vault
            KEY1: 'map-vault.key1'
            KEY2: 'map-vault.key2'
          import-secrets:
            - 'map-vault2'
  demo:
    replicas: 1
    containers:
      nginx:
        image: registry.hub.docker.com/library/nginx
        version: alpine
        listen: #Port listen by the container
          - 8080
        volumes:
          www:
            mount-path: '/var'
            add:
              - 'nginx/www'
          config:
            mount-path: '/etc/nginx/conf.d/'
            add:
              - 'nginx/conf.d/default.conf'

#Pods expositions
services:
  php-service: #Service name
    pod: "php-pods" #Pod name, use service name by default
    internal: false #If false, a load balancer is use to access it from outside
    protocol: 'TCP' #Or UDP
    ports:
      - listen: 9876 #Port listened
        target: 8080 #Pod's port targeted
  demo: #Service name
    ports:
      - listen: 8080 #Port listened
        target: 8080 #Pod's port targeted

#Ingresses configuration
ingresses:
  demo: #rule name
    host: demo-paas.teknoo.software
    tls:
      secret: "demo_vault" #Configure the orchestrator to fetch value from vault
    service: #default service
      name: demo
      port: 8080
    paths:
      - path: /php
        service:
          name: php-service
          port: 9876

EOF;

                $conductor->prepare(
                    $conf,
                    $promise
                );

                return $this;
            }

            public function hasDirectory(string $path, PromiseInterface $promise): JobWorkspaceInterface
            {
                $promise->success();

                return $this;
            }

            public function runInRepositoryPath(callable $callback): JobWorkspaceInterface
            {
                $callback('/foo');

                return $this;
            }

        };

        $this->sfContainer->set(
            JobWorkspaceInterface::class,
            $workspace
        );
    }

    /**
     * @Given a git cloning agent
     */
    public function aGitCloningAgent()
    {
        $cloningAgent = new class implements CloningAgentInterface {
            use ImmutableTrait;

            private ?JobWorkspaceInterface $workspace = null;

            public function configure(
                SourceRepositoryInterface $repository,
                JobWorkspaceInterface $workspace
            ): CloningAgentInterface {
                $that = clone $this;

                $that->workspace = $workspace;

                return $that;
            }

            public function run(): CloningAgentInterface
            {
                $this->workspace->prepareRepository($this);

                return $this;
            }

            public function cloningIntoPath(string $jobRootPath, string $repositoryFolder): CloningAgentInterface
            {
                return $this;
            }
        };

        $this->sfContainer->set(
            CloningAgentInterface::class,
            $cloningAgent
        );
    }

    /**
     * @Given a composer hook as hook builder
     */
    public function aComposerHookAsHookBuilder()
    {
        $hook = new HookMock();

        $hooks = ['composer' => clone $hook, 'hook-id' => clone $hook];
        $collection = new class ($hooks) implements HooksCollectionInterface {

            private iterable $hooks;

            public function __construct(iterable $hooks)
            {
                $this->hooks = $hooks;
            }

            public function getIterator(): Traversable
            {
                yield from $this->hooks;
            }
        };

        $this->sfContainer->set(
            HooksCollectionInterface::class,
            $collection
        );
    }

    /**
     * @Given an image builder
     */
    public function anImageBuilder()
    {
        $builder = new class implements BuilderInterface {
            public function configure(string $projectId, string $url, ?IdentityInterface $auth): BuilderInterface
            {
                return clone $this;
            }

            public function buildImages(
                CompiledDeploymentInterface $compiledDeployment,
                string $workingPath,
                PromiseInterface $promise
            ): BuilderInterface {
                $promise->success('foo');

                return $this;
            }

            public function buildVolumes(
                CompiledDeploymentInterface $compiledDeployment,
                string $workingPath,
                PromiseInterface $promise
            ): BuilderInterface {
                $promise->success('bar');

                return $this;
            }
        };

        $this->sfContainer->set(
            BuilderInterface::class,
            $builder
        );
    }

    /**
     * @Given a cluster client
     */
    public function aClusterClient()
    {
        $client = new class implements DriverInterface {
            public function configure(string $url, ?IdentityInterface $identity): DriverInterface
            {
                return clone $this;
            }

            public function deploy(CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise): DriverInterface
            {
                $promise->success(['foo' => 'bar']);

                return $this;
            }

            public function expose(CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise): DriverInterface
            {
                $promise->success(['foo' => 'bar']);
                return $this;
            }
        };

        $this->sfContainer->get(Directory::class)->register('behat', $client);
    }

    /**
     * @Given an OCI builder
     */
    public function anOciBuilder()
    {
        $generator = new Generator();
        $mock = $generator->getMock(
            type: Process::class,
            callOriginalConstructor: false,
            callOriginalClone: false,
            callOriginalMethods: false,
        );

        $mock->expects(new AnyInvokedCountMatcher())
            ->method('isSuccessful')
            ->willReturn(true);

        $this->sfContainer->set(
            ProcessFactoryInterface::class,
            new class ($mock) implements ProcessFactoryInterface {
                public function __construct(
                    private Process $process,
                ) {
                }

                public function __invoke(string $cwd): Process
                {
                    return $this->process;
                }
            }
        );

        $this->sfContainer->get(DiContainer::class)->set(
            'teknoo.east.paas.img_builder.build.platforms',
            'kubernetes',
        );
    }

    /**
     * @Given a kubernetes client
     */
    public function aKubernetesClient()
    {
        $generator = new Generator();
        $mock = $generator->getMock(
            type: Client::class,
            callOriginalConstructor: false,
            callOriginalClone: false,
            callOriginalMethods: false,
        );

        $repoMock = $generator->getMock(
            type: Repository::class,
            callOriginalConstructor: false,
            callOriginalClone: false,
            callOriginalMethods: false,
        );

        $mock->expects(new AnyInvokedCountMatcher())
            ->method('__call')
            ->willReturn($repoMock);

        $repoMock->expects(new AnyInvokedCountMatcher())
            ->method('exists')
            ->willReturn(false);

        $this->manifests = [];
        $repoMock->expects(new AnyInvokedCountMatcher())
            ->method('create')
            ->willReturnCallback(
                function (Model $model): array {
                    $this->manifests[$model::class][] = $model->toArray();

                    return ['foo'];
                }
            );

        $this->sfContainer->set(
            ClientFactoryInterface::class,
            new class ($mock) implements ClientFactoryInterface {
                public function __construct(
                    private Client $client,
                ) {
                }

                public function __invoke(
                    string $master,
                    ?ClusterCredentials $credentials,
                    ?RepositoryRegistry $repositoryRegistry = null
                ): Client {
                    return $this->client;
                }
            }
        );

        $this->clusterType = 'kubernetes';
    }

    /**
     * @Given a cluster supporting hierarchical namespace
     */
    public function aClusterSupportingHierarchicalNamespace()
    {
        self::$useHnc = true;
    }

    public static function compareCD(CompiledDeploymentInterface $cd): void
    {
        try {
            Assert::assertEquals(
                var_export($ecd = (include('expectedCD.php'))(self::$useHnc), true),
                var_export($cd, true)
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * @Then some kunbernetes manifests have been created
     */
    public function someKunbernetesManifestsHaveBeenCreated()
    {
        $excepted = <<<'EOF'
{
    "Maclof\\Kubernetes\\Models\\SubnamespaceAnchor": [
        {
            "metadata": {
                "name": "test",
                "namespace": "",
                "labels": {
                    "name": "test"
                }
            }
        },
        {
            "metadata": {
                "name": "test",
                "namespace": "",
                "labels": {
                    "name": "test"
                }
            }
        }
    ],
    "Maclof\\Kubernetes\\Models\\Secret": [
        {
            "metadata": {
                "name": "map-vault-secret",
                "namespace": "test",
                "labels": {
                    "name": "map-vault"
                }
            },
            "type": "Opaque",
            "data": {
                "key1": "dmFsdWUx",
                "key2": "Zm9v"
            }
        },
        {
            "metadata": {
                "name": "map-vault2-secret",
                "namespace": "test",
                "labels": {
                    "name": "map-vault2"
                }
            },
            "type": "Opaque",
            "data": {
                "hello": "d29ybGQ="
            }
        },
        {
            "metadata": {
                "name": "volume-vault-secret",
                "namespace": "test",
                "labels": {
                    "name": "volume-vault"
                }
            },
            "type": "foo",
            "data": {
                "foo": "YmFy",
                "bar": "Zm9v"
            }
        }
    ],
    "Maclof\\Kubernetes\\Models\\ConfigMap": [
        {
            "metadata": {
                "name": "map1-map",
                "namespace": "test",
                "labels": {
                    "name": "map1"
                }
            },
            "data": {
                "key1": "value1",
                "key2": "foo"
            }
        },
        {
            "metadata": {
                "name": "map2-map",
                "namespace": "test",
                "labels": {
                    "name": "map2"
                }
            },
            "data": {
                "foo": "bar",
                "bar": "foo"
            }
        }
    ],
    "Maclof\\Kubernetes\\Models\\PersistentVolumeClaim": [
        {
            "metadata": {
                "name": "data",
                "namespace": "test",
                "labels": {
                    "name": "data"
                }
            },
            "spec": {
                "accessModes": [
                    "ReadWriteOnce"
                ],
                "storageClassName": "nfs",
                "resources": {
                    "requests": {
                        "storage": "3Gi"
                    }
                }
            }
        }
    ],
    "Maclof\\Kubernetes\\Models\\ReplicaSet": [
        {
            "metadata": {
                "name": "php-pods-ctrl-v1",
                "namespace": "test",
                "labels": {
                    "name": "php-pods"
                },
                "annotations": {
                    "teknoo.space.version": "v1"
                }
            },
            "spec": {
                "replicas": 2,
                "selector": {
                    "matchLabels": {
                        "vname": "php-pods-v1"
                    }
                },
                "template": {
                    "metadata": {
                        "name": "php-pods-pod",
                        "namespace": "test",
                        "labels": {
                            "name": "php-pods",
                            "vname": "php-pods-v1"
                        }
                    },
                    "spec": {
                        "hostAliases": [
                            {
                                "hostnames": [
                                    "php-run"
                                ],
                                "ip": "127.0.0.1"
                            }
                        ],
                        "containers": [
                            {
                                "name": "php-run",
                                "image": "fooBar\/php-run-jobid:7.4",
                                "imagePullPolicy": "Always",
                                "ports": [
                                    {
                                        "containerPort": 8080
                                    }
                                ],
                                "envFrom": [
                                    {
                                        "secretRef": {
                                            "name": "map-vault2-secret"
                                        }
                                    },
                                    {
                                        "configMapRef": {
                                            "name": "map2-map"
                                        }
                                    }
                                ],
                                "env": [
                                    {
                                        "name": "SERVER_SCRIPT",
                                        "value": "\/opt\/app\/src\/server.php"
                                    },
                                    {
                                        "name": "KEY1",
                                        "valueFrom": {
                                            "secretKeyRef": {
                                                "name": "map-vault-secret",
                                                "key": "key1"
                                            }
                                        }
                                    },
                                    {
                                        "name": "KEY2",
                                        "valueFrom": {
                                            "secretKeyRef": {
                                                "name": "map-vault-secret",
                                                "key": "key2"
                                            }
                                        }
                                    },
                                    {
                                        "name": "KEY0",
                                        "valueFrom": {
                                            "configMapKeyRef": {
                                                "name": "map1-map",
                                                "key": "key0"
                                            }
                                        }
                                    }
                                ],
                                "volumeMounts": [
                                    {
                                        "name": "extra-jobid-volume",
                                        "mountPath": "\/opt\/extra",
                                        "readOnly": true
                                    },
                                    {
                                        "name": "data-volume",
                                        "mountPath": "\/opt\/data",
                                        "readOnly": false
                                    },
                                    {
                                        "name": "map-volume",
                                        "mountPath": "\/map",
                                        "readOnly": false
                                    },
                                    {
                                        "name": "vault-volume",
                                        "mountPath": "\/vault",
                                        "readOnly": false
                                    }
                                ]
                            }
                        ],
                        "initContainers": [
                            {
                                "name": "extra-jobid",
                                "image": "fooBar\/extra-jobid",
                                "imagePullPolicy": "Always",
                                "volumeMounts": [
                                    {
                                        "name": "extra-jobid-volume",
                                        "mountPath": "\/opt\/extra",
                                        "readOnly": false
                                    }
                                ]
                            }
                        ],
                        "volumes": [
                            {
                                "name": "extra-jobid-volume",
                                "emptyDir": []
                            },
                            {
                                "name": "data-volume",
                                "persistentVolumeClaim": {
                                    "claimName": "data"
                                }
                            },
                            {
                                "name": "map-volume",
                                "configMap": {
                                    "name": "map2-map"
                                }
                            },
                            {
                                "name": "vault-volume",
                                "secret": {
                                    "secretName": "volume-vault-secret"
                                }
                            }
                        ]
                    }
                }
            }
        },
        {
            "metadata": {
                "name": "demo-ctrl-v1",
                "namespace": "test",
                "labels": {
                    "name": "demo"
                },
                "annotations": {
                    "teknoo.space.version": "v1"
                }
            },
            "spec": {
                "replicas": 1,
                "selector": {
                    "matchLabels": {
                        "vname": "demo-v1"
                    }
                },
                "template": {
                    "metadata": {
                        "name": "demo-pod",
                        "namespace": "test",
                        "labels": {
                            "name": "demo",
                            "vname": "demo-v1"
                        }
                    },
                    "spec": {
                        "hostAliases": [
                            {
                                "hostnames": [
                                    "nginx"
                                ],
                                "ip": "127.0.0.1"
                            }
                        ],
                        "containers": [
                            {
                                "name": "nginx",
                                "image": "fooBar\/nginx-jobid:alpine",
                                "imagePullPolicy": "Always",
                                "ports": [
                                    {
                                        "containerPort": 8080
                                    }
                                ]
                            }
                        ]
                    }
                }
            }
        }
    ],
    "Maclof\\Kubernetes\\Models\\Service": [
        {
            "metadata": {
                "name": "php-service-service",
                "namespace": "test",
                "labels": {
                    "name": "php-service"
                }
            },
            "spec": {
                "selector": {
                    "name": "php-pods"
                },
                "type": "LoadBalancer",
                "ports": [
                    {
                        "name": "php-service-9876",
                        "protocol": "TCP",
                        "port": 9876,
                        "targetPort": 8080
                    }
                ]
            }
        },
        {
            "metadata": {
                "name": "demo-service",
                "namespace": "test",
                "labels": {
                    "name": "demo"
                }
            },
            "spec": {
                "selector": {
                    "name": "demo"
                },
                "type": "ClusterIP",
                "ports": [
                    {
                        "name": "demo-8080",
                        "protocol": "TCP",
                        "port": 8080,
                        "targetPort": 8080
                    }
                ]
            }
        }
    ],
    "Maclof\\Kubernetes\\Models\\Ingress": [
        {
            "metadata": {
                "name": "demo-ingress",
                "namespace": "test",
                "labels": {
                    "name": "demo"
                },
                "annotations": {
                    "foo": "bar"
                }
            },
            "spec": {
                "rules": [
                    {
                        "host": "demo-paas.teknoo.software",
                        "http": {
                            "paths": [
                                {
                                    "path": "\/",
                                    "pathType": "Prefix",
                                    "backend": {
                                        "service": {
                                            "name": "demo-service",
                                            "port": {
                                                "number": 8080
                                            }
                                        }
                                    }
                                },
                                {
                                    "path": "\/php",
                                    "pathType": "Prefix",
                                    "backend": {
                                        "service": {
                                            "name": "php-service-service",
                                            "port": {
                                                "number": 9876
                                            }
                                        }
                                    }
                                }
                            ]
                        }
                    }
                ],
                "tls": [
                    {
                        "hosts": [
                            "demo-paas.teknoo.software"
                        ],
                        "secretName": "demo_vault-secret"
                    }
                ]
            }
        }
    ]
}
EOF;

        $json = json_encode($this->manifests, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
        Assert::assertEquals(
            $excepted,
            $json
        );
    }
}
