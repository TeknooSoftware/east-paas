<?php

declare(strict_types=1);

namespace Teknoo\Tests\East\Paas\Behat;

use Behat\Behat\Context\Context;
use DI\Container as DiContainer;
use DateTime;
use Doctrine\ODM\MongoDB\Query\Builder as QueryBuilder;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ObjectManager;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Kubernetes\RepositoryRegistry;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder as SfContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Teknoo\DI\SymfonyBridge\DIBridgeBundle;
use Teknoo\East\CommonBundle\TeknooEastCommonBundle;
use Teknoo\East\Common\Object\User;
use Teknoo\East\Common\Service\DatesService;
use Teknoo\East\FoundationBundle\EastFoundationBundle;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Account;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Project;
use Teknoo\East\Paas\Infrastructures\EastPaasBundle\TeknooEastPaasBundle;
use Teknoo\East\Paas\Infrastructures\Image\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\ImageRegistry;
use Teknoo\East\Paas\Object\Job as OriJob;
use Teknoo\East\Paas\Object\Project as OriProject;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;
use Traversable;

use function base64_encode;
use function dirname;
use function file_exists;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function random_int;
use function str_replace;
use function stripslashes;
use function strlen;
use function strtolower;
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
    private static string $projectPrefix = '';

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

    private static bool $useHnc = false;

    private static string $hncSuffix = '';

    private array $manifests = [];

    public bool $slowDb = false;

    public bool $slowBuilder = false;

    private ?string $paasFile = null;

    public array $additionalsParameters = [];

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

                foreach ($this->context->additionalsParameters as $name => &$params) {
                    $container->setParameter($name, $params);
                }

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
        $this->slowDb = false;
        $this->slowBuilder = false;
        $this->paasFile = null;
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
        $this->objectManager = new class([$this, 'getRepository'], $this) implements ObjectManager {
            private $repositories;

            public function __construct(
                callable $repositories,
                private FeatureContext $context,
            ){
                $this->repositories = $repositories;
            }

            public function find($className, $id) {}
            public function persist($object) {
                if ($this->context->slowDb) {
                    $expectedTime = time() + 20;
                    while (time() < $expectedTime) {
                        $x = str_repeat('x', 100000);
                    }
                }
            }
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
        $this->additionalsParameters = [];
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
     * @Given a project on this account :name with the id :id and a prefix :prefix
     */
    public function aProjectOnThisAccountWithTheId($name, $id, $prefix='')
    {
        $this->projectName = $name;
        $this->projectId = $id;
        self::$projectPrefix = $prefix;

        $this->repositories[Project::class]->register(
            $id,
            $this->project = (new Project($this->account))->setId($this->projectId)->setName($this->projectName)
        );

        if (self::$useHnc) {
            self::$hncSuffix = '-' . str_replace(' ', '', strtolower($name));
        } else {
            self::$hncSuffix = '';
        }
    }

    /**
     * @Given a cluster :name dedicated to the environment :id
     */
    public function aClusterDedicatedToTheEnvironment($name, $id)
    {
        $this->clusterName = $name;
        $this->envName = $id;

        $this->project->setClusters([
            $this->cluster = (new Cluster())
                ->setId('cluster-id')
                ->setType($this->clusterType)
                ->setProject($this->project)
                ->setName($this->clusterName)
                ->setEnvironment($this->environment = new Environment($this->envName))
                ->setAddress('https://foo-bar')
                ->setIdentity(
                    (
                        new ClusterCredentials(
                            clientCertificate:  'fooBar',
                            clientKey: 'barKey',
                            token:  'fooBar',
                        )
                    )->setId('cluster-auth-id')
                )
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
     * @Given a oci repository
     */
    public function aOciRepository()
    {
        $this->project->setImagesRegistry(
            $this->imagesRegistry = (
                new ImageRegistry(
                    apiUrl: 'https://foo.bar',
                    identity: (
                        new XRegistryAuth(
                            username:  'fooBar',
                            password:  'fooBar',
                            email:  'fooBar',
                            auth: '',
                            serverAddress: 'fooBar',
                        )
                    )->setId('xauth-id')
                    ,
                )
            )
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
        $body = json_encode(
            value: $this->getNormalizedJob(
                variables: [
                    'FOO' => 'foo',
                ],
                hnc: self::$useHnc,
                jobId: 'jobid',
                extra: [
                    'foo' => 'bar',
                ],
            )
        );

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

    private function getNormalizedJob(
        array $variables = [],
        bool $hnc = false,
        string $jobId = '',
        array $extra = []
    ): array {
        return [
            '@class' => OriJob::class,
            'id' => $jobId,
            'project' => [
                '@class' => OriProject::class,
                'id' => $this->projectId,
                'name' => $this->projectName,
            ],
            'base_namespace' => 'behat-test',
            'prefix' => self::$projectPrefix,
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
                'identity' => [
                    '@class' => XRegistryAuth::class,
                    'id' => 'xauth-id',
                    'username' => 'fooBar',
                    'password' => 'fooBar',
                    'email' => 'fooBar',
                    'auth' => '',
                    'server_address' => 'fooBar',
                ],
            ],
            'clusters' => [
                [
                    '@class' => Cluster::class,
                    'id' => 'cluster-id',
                    'name' => $this->clusterName,
                    'type' => $this->clusterType,
                    'address' => 'https://foo-bar',
                    'identity' => [
                        '@class' => ClusterCredentials::class,
                        'id' => 'cluster-auth-id',
                        'client_certificate' => 'fooBar',
                        'client_key' => 'barKey',
                        'token' => 'fooBar',
                        'username' => '',
                        'password' => '',
                    ],
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
            'extra' => $extra,
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
     * @Given a project with a complete paas file
     */
    public function aProjectWithACompletePaasFile()
    {
        $this->paasFile = __DIR__ . '/paas.yaml';
    }

    /**
     * @Given a project with a paas file using extends
     */
    public function aProjectWithAPaasFileUsingExtends()
    {
        $this->paasFile = __DIR__ . '/paas.with-extends.yaml';
    }

    /**
     * @Given extensions libraries provided by administrators
     */
    public function extensionsLibrariesProvidedByAdministrators()
    {
        $this->additionalsParameters['teknoo.east.paas.ingresses.library'] = [
            'demo-extends' => [
                'service' => [
                    'name' => 'demo',
                    'port' => 8080,
                ],
            ],
        ];

        $this->additionalsParameters['teknoo.east.paas.pods.library'] = [
            'php-pods-extends' => [
                'replicas' => 2,
                'requires' => [
                    'x86_64',
                    'avx',
                ],
                'upgrade' => [
                    'max-upgrading-pods' => 2,
                    'max-unavailable-pods' => 1,
                ],
                'containers' => [
                    'php-run' => [
                        'image' => 'registry.teknoo.software/php-run',
                        'version' => 7.4,
                        'listen' => [8080],
                        'volumes' => [
                            'extra' => [
                                'from' => 'extra',
                                'mount-path' => '/opt/extra',
                            ],
                            'data' => [
                                'mount-path' => '/opt/data',
                                'persistent' => true,
                                'storage-size' => '3Gi',
                            ],
                            'data-replicated' => [
                                'mount-path' => '/opt/data-replicated',
                                'persistent' => true,
                                'storage-provider' => 'replicated-provider',
                                'storage-size' => '3Gi',
                            ],
                            'map' => [
                                'mount-path' => '/map',
                                'from-map' => 'map2',
                            ],
                        ],
                        'variables' => [
                            'SERVER_SCRIPT' => '${SERVER_SCRIPT}',
                        ],
                        'healthcheck' => [
                            'initial-delay-seconds' => 10,
                            'period-seconds' => 30,
                            'probe' => [
                                'command' => ['ps', 'aux', 'php'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->additionalsParameters['teknoo.east.paas.containers.library'] =  [
            'bash-extends' => [
                'image' => 'registry.hub.docker.com/bash',
                'version' => 'alpine',
            ],
        ];

        $this->additionalsParameters['teknoo.east.paas.services.library'] = [
            'php-pods-extends' => [
                'pod' => 'php-pods',
                'internal' => false,
                'protocol' => Transport::Tcp->value,
                'ports' => [
                    [
                        'listen' => 9876,
                        'target' => 8080,
                    ],
                ],
            ],
        ];
    }


    /**
     * @Given a job workspace agent
     */
    public function aJobWorkspaceAgent()
    {
        $workspace = new class ($this->paasFile) implements JobWorkspaceInterface {
            use ImmutableTrait;

            public function __construct(
                private readonly ?string $paasFile,
            ) {
            }

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
                if (empty($this->paasFile) || !file_exists($this->paasFile)) {
                    throw new RuntimeException('Error, the paas file was not defined for this test');
                }

                $conf = file_get_contents($this->paasFile);

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
        $builder = new class ($this) implements BuilderInterface {
            public function __construct(
                private FeatureContext $context
            ) {
            }

            public function configure(string $projectId, string $url, ?IdentityInterface $auth): BuilderInterface
            {
                return clone $this;
            }

            public function buildImages(
                CompiledDeploymentInterface $compiledDeployment,
                string $workingPath,
                PromiseInterface $promise
            ): BuilderInterface {
                if ($this->context->slowBuilder) {
                    $expectedTime = time() + 20;
                    while (time() < $expectedTime) {
                        $x = str_repeat('x', 100000);
                    }
                }

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
     * @Given simulate a very slowly database
     */
    public function simulateAVerySlowlyDatabase()
    {
        $this->slowDb = true;
    }

    /**
     * @Given simulate a too long image building
     */
    public function simulateATooLongImageBuilding()
    {
        $this->slowBuilder = true;
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
            ->willReturnCallback(
                function () {
                    if ($this->slowBuilder) {
                        $expectedTime = time() + 20;
                        while (time() < $expectedTime) {
                            $x = str_repeat('x', 100000);
                        }
                    }

                    return true;
                }
            );

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

        $this->manifests = [];
        $repoMock->expects(new AnyInvokedCountMatcher())
            ->method('apply')
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
                var_export($ecd = (include('expectedCD.php'))(
                    self::$useHnc,
                    self::$hncSuffix,
                    self::$projectPrefix
                ), true),
                var_export($cd, true)
            );
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * @Then some Kubernetes manifests have been created
     */
    public function someKubernetesManifestsHaveBeenCreated()
    {
        $hncSuffix = self::$hncSuffix;
        $nameHnc = trim($hncSuffix, '-');

        $prefix = self::$projectPrefix;
        if (!empty($prefix)) {
            $prefix .= '-';
        }

        $hncManifest = '';
        if (self::$useHnc) {
            $hncManifest = <<<"EOF"
"Teknoo\\Kubernetes\\Model\\SubnamespaceAnchor": [
        {
            "metadata": {
                "name": "{$nameHnc}",
                "namespace": "behat-test",
                "labels": {
                    "name": "behat-test{$hncSuffix}"
                }
            }
        }
    ],
    
EOF;
        }

        $secret = base64_encode($prefix . 'world');

        $excepted = <<<"EOF"
{
    $hncManifest"Teknoo\\Kubernetes\\Model\\Secret": [
        {
            "metadata": {
                "name": "{$prefix}map-vault-secret",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}map-vault"
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
                "name": "{$prefix}map-vault2-secret",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}map-vault2"
                }
            },
            "type": "Opaque",
            "data": {
                "hello": "$secret"
            }
        },
        {
            "metadata": {
                "name": "{$prefix}volume-vault-secret",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}volume-vault"
                }
            },
            "type": "foo",
            "data": {
                "foo": "YmFy",
                "bar": "Zm9v"
            }
        }
    ],
    "Teknoo\\Kubernetes\\Model\\ConfigMap": [
        {
            "metadata": {
                "name": "{$prefix}map1-map",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}map1"
                }
            },
            "data": {
                "key1": "value1",
                "key2": "foo"
            }
        },
        {
            "metadata": {
                "name": "{$prefix}map2-map",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}map2"
                }
            },
            "data": {
                "foo": "bar",
                "bar": "{$prefix}foo"
            }
        }
    ],
    "Teknoo\\Kubernetes\\Model\\PersistentVolumeClaim": [
        {
            "metadata": {
                "name": "{$prefix}data",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}data"
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
        },
        {
            "metadata": {
                "name": "{$prefix}data-replicated",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}data-replicated"
                }
            },
            "spec": {
                "accessModes": [
                    "ReadWriteOnce"
                ],
                "storageClassName": "replicated-provider",
                "resources": {
                    "requests": {
                        "storage": "3Gi"
                    }
                }
            }
        }
    ],
    "Teknoo\\Kubernetes\\Model\\Deployment": [
        {
            "metadata": {
                "name": "{$prefix}php-pods-dplmt",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}php-pods"
                },
                "annotations": {
                    "teknoo.space.version": "v1"
                }
            },
            "spec": {
                "replicas": 2,
                "strategy": {
                    "type": "RollingUpdate",
                    "rollingUpdate": {
                        "maxSurge": 2,
                        "maxUnavailable": 1
                    }
                },
                "selector": {
                    "matchLabels": {
                        "name": "{$prefix}php-pods"
                    }
                },
                "template": {
                    "metadata": {
                        "name": "{$prefix}php-pods-pod",
                        "namespace": "behat-test{$hncSuffix}",
                        "labels": {
                            "name": "{$prefix}php-pods",
                            "vname": "{$prefix}php-pods-v1"
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
                                "image": "https://foo.bar/php-run-jobid:7.4",
                                "imagePullPolicy": "Always",
                                "ports": [
                                    {
                                        "containerPort": 8080
                                    }
                                ],
                                "envFrom": [
                                    {
                                        "secretRef": {
                                            "name": "{$prefix}map-vault2-secret"
                                        }
                                    },
                                    {
                                        "configMapRef": {
                                            "name": "{$prefix}map2-map"
                                        }
                                    }
                                ],
                                "env": [
                                    {
                                        "name": "SERVER_SCRIPT",
                                        "value": "/opt/app/src/server.php"
                                    },
                                    {
                                        "name": "KEY1",
                                        "valueFrom": {
                                            "secretKeyRef": {
                                                "name": "{$prefix}map-vault-secret",
                                                "key": "key1"
                                            }
                                        }
                                    },
                                    {
                                        "name": "KEY2",
                                        "valueFrom": {
                                            "secretKeyRef": {
                                                "name": "{$prefix}map-vault-secret",
                                                "key": "key2"
                                            }
                                        }
                                    },
                                    {
                                        "name": "KEY0",
                                        "valueFrom": {
                                            "configMapKeyRef": {
                                                "name": "{$prefix}map1-map",
                                                "key": "key0"
                                            }
                                        }
                                    }
                                ],
                                "volumeMounts": [
                                    {
                                        "name": "extra-jobid-volume",
                                        "mountPath": "/opt/extra",
                                        "readOnly": true
                                    },
                                    {
                                        "name": "data-volume",
                                        "mountPath": "/opt/data",
                                        "readOnly": false
                                    },
                                    {
                                        "name": "data-replicated-volume",
                                        "mountPath": "/opt/data-replicated",
                                        "readOnly": false
                                    },
                                    {
                                        "name": "map-volume",
                                        "mountPath": "/map",
                                        "readOnly": false
                                    },
                                    {
                                        "name": "vault-volume",
                                        "mountPath": "/vault",
                                        "readOnly": false
                                    }
                                ],
                                "livenessProbe": {
                                    "initialDelaySeconds": 10,
                                    "periodSeconds": 30,
                                    "exec": {
                                        "command": [
                                            "ps",
                                            "aux",
                                            "php"
                                        ]
                                    },
                                    "successThreshold": 1,
                                    "failureThreshold": 1
                                }
                            }
                        ],
                        "affinity": {
                            "nodeAffinity": {
                                "requiredDuringSchedulingIgnoredDuringExecution": {
                                    "nodeSelectorTerms": [
                                        {
                                            "matchExpressions": [
                                                {
                                                    "key": "paas.east.teknoo.net/x86_64",
                                                    "operator": "Exists"
                                                },
                                                {
                                                    "key": "paas.east.teknoo.net/avx",
                                                    "operator": "Exists"
                                                }
                                            ]
                                        }
                                    ]
                                }
                            }
                        },
                        "initContainers": [
                            {
                                "name": "extra-jobid",
                                "image": "https://foo.bar/extra-jobid",
                                "imagePullPolicy": "Always",
                                "volumeMounts": [
                                    {
                                        "name": "extra-jobid-volume",
                                        "mountPath": "/opt/extra",
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
                                    "claimName": "{$prefix}data"
                                }
                            },
                            {
                                "name": "data-replicated-volume",
                                "persistentVolumeClaim": {
                                    "claimName": "{$prefix}data-replicated"
                                }
                            },
                            {
                                "name": "map-volume",
                                "configMap": {
                                    "name": "{$prefix}map2-map"
                                }
                            },
                            {
                                "name": "vault-volume",
                                "secret": {
                                    "secretName": "{$prefix}volume-vault-secret"
                                }
                            }
                        ]
                    }
                }
            }
        },
        {
            "metadata": {
                "name": "{$prefix}shell-dplmt",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}shell"
                },
                "annotations": {
                    "teknoo.space.version": "v1"
                }
            },
            "spec": {
                "replicas": 1,
                "strategy": {
                    "type": "RollingUpdate",
                    "rollingUpdate": {
                        "maxSurge": 1,
                        "maxUnavailable": 0
                    }
                },
                "selector": {
                    "matchLabels": {
                        "name": "{$prefix}shell"
                    }
                },
                "template": {
                    "metadata": {
                        "name": "{$prefix}shell-pod",
                        "namespace": "behat-test{$hncSuffix}",
                        "labels": {
                            "name": "{$prefix}shell",
                            "vname": "{$prefix}shell-v1"
                        }
                    },
                    "spec": {
                        "hostAliases": [
                            {
                                "hostnames": [
                                    "sleep"
                                ],
                                "ip": "127.0.0.1"
                            }
                        ],
                        "containers": [
                            {
                                "name": "sleep",
                                "image": "registry.hub.docker.com/bash:alpine",
                                "imagePullPolicy": "Always",
                                "ports": []
                            }
                        ]
                    }
                }
            }
        },
        {
            "metadata": {
                "name": "{$prefix}demo-dplmt",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}demo"
                },
                "annotations": {
                    "teknoo.space.version": "v1"
                }
            },
            "spec": {
                "replicas": 1,
                "strategy": {
                    "type": "Recreate"
                },
                "selector": {
                    "matchLabels": {
                        "name": "{$prefix}demo"
                    }
                },
                "template": {
                    "metadata": {
                        "name": "{$prefix}demo-pod",
                        "namespace": "behat-test{$hncSuffix}",
                        "labels": {
                            "name": "{$prefix}demo",
                            "vname": "{$prefix}demo-v1"
                        }
                    },
                    "spec": {
                        "hostAliases": [
                            {
                                "hostnames": [
                                    "nginx",
                                    "waf",
                                    "blackfire"
                                ],
                                "ip": "127.0.0.1"
                            }
                        ],
                        "containers": [
                            {
                                "name": "nginx",
                                "image": "https://foo.bar/nginx-jobid:alpine",
                                "imagePullPolicy": "Always",
                                "ports": [
                                    {
                                        "containerPort": 8080
                                    },
                                    {
                                        "containerPort": 8181
                                    }
                                ],
                                "livenessProbe": {
                                    "initialDelaySeconds": 10,
                                    "periodSeconds": 30,
                                    "httpGet": {
                                        "path": "/status",
                                        "port": 8080,
                                        "scheme": "HTTPS"
                                    },
                                    "successThreshold": 3,
                                    "failureThreshold": 2
                                }
                            },
                            {
                                "name": "waf",
                                "image": "registry.hub.docker.com/library/waf:alpine",
                                "imagePullPolicy": "Always",
                                "ports": [
                                    {
                                        "containerPort": 8181
                                    }
                                ],
                                "livenessProbe": {
                                    "initialDelaySeconds": 10,
                                    "periodSeconds": 30,
                                    "tcpSocket": {
                                        "port": 8181
                                    },
                                    "successThreshold": 1,
                                    "failureThreshold": 1
                                }
                            },
                            {
                                "name": "blackfire",
                                "image": "blackfire/blackfire:2",
                                "imagePullPolicy": "Always",
                                "ports": [
                                    {
                                        "containerPort": 8307
                                    }
                                ],
                                "env": [
                                    {
                                        "name": "BLACKFIRE_SERVER_ID",
                                        "value": "foo"
                                    },
                                    {
                                        "name": "BLACKFIRE_SERVER_TOKEN",
                                        "value": "bar"
                                    }
                                ]
                            }
                        ],
                        "securityContext": {
                            "fsGroup": 1000
                        }
                    }
                }
            }
        }
    ],
    "Teknoo\\Kubernetes\\Model\\Service": [
        {
            "metadata": {
                "name": "{$prefix}php-service",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}php-service"
                }
            },
            "spec": {
                "selector": {
                    "name": "{$prefix}php-pods"
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
                "name": "{$prefix}demo",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}demo"
                }
            },
            "spec": {
                "selector": {
                    "name": "{$prefix}demo"
                },
                "type": "ClusterIP",
                "ports": [
                    {
                        "name": "demo-8080",
                        "protocol": "TCP",
                        "port": 8080,
                        "targetPort": 8080
                    },
                    {
                        "name": "demo-8181",
                        "protocol": "TCP",
                        "port": 8181,
                        "targetPort": 8181
                    }
                ]
            }
        }
    ],
    "Teknoo\\Kubernetes\\Model\\Ingress": [
        {
            "metadata": {
                "name": "{$prefix}demo-ingress",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}demo"
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
                                    "path": "/",
                                    "pathType": "Prefix",
                                    "backend": {
                                        "service": {
                                            "name": "{$prefix}demo",
                                            "port": {
                                                "number": 8080
                                            }
                                        }
                                    }
                                },
                                {
                                    "path": "/php",
                                    "pathType": "Prefix",
                                    "backend": {
                                        "service": {
                                            "name": "{$prefix}php-service",
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
                        "secretName": "{$prefix}demo-vault-secret"
                    }
                ]
            }
        },
        {
            "metadata": {
                "name": "{$prefix}demo-secure-ingress",
                "namespace": "behat-test{$hncSuffix}",
                "labels": {
                    "name": "{$prefix}demo-secure"
                },
                "annotations": {
                    "foo": "bar",
                    "nginx.ingress.kubernetes.io/backend-protocol": "HTTPS"
                }
            },
            "spec": {
                "rules": [
                    {
                        "host": "demo-secure.teknoo.software",
                        "http": {
                            "paths": [
                                {
                                    "path": "/",
                                    "pathType": "Prefix",
                                    "backend": {
                                        "service": {
                                            "name": "{$prefix}demo",
                                            "port": {
                                                "number": 8181
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
                            "demo-secure.teknoo.software"
                        ],
                        "secretName": "{$prefix}demo-vault-secret"
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
            stripslashes($json)
        );
    }
}
