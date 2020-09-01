<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use DI\Bridge\Symfony\Kernel as BaseKernel;
use DI\ContainerBuilder as DIContainerBuilder;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Client\ClientInterface as PsrClient;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as SfContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Item;
use Teknoo\East\Website\Doctrine\Object\Media;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Object\User;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Account;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Object\BillingInformation;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\DockerRepository;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job as OriJob;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
use Teknoo\East\Paas\Object\PaymentInformation;
use Teknoo\East\Paas\Object\Project as OriProject;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Project;
use Teknoo\East\Website\Service\DatesService;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Recipe\Step\Misc\PushResult;
use Symfony\Component\DependencyInjection\Container;
use function DI\create;
use function DI\get;

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
     * @var Container
     */
    private $phpDiContainer;

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
     * @var DockerRepository
     */
    private $imagesRepository;

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

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     *
     * @param KernelInterface $kernel
     * @param Container $container
     */
    public function __construct()
    {
        $this->initiateSymfonyKernel();
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

            public function getCacheDir()
            {
                return \dirname(__DIR__, 2).'/tests/var/cache';
            }

            public function getLogDir()
            {
                return \dirname(__DIR__, 2).'/tests/var/logs';
            }

            public function registerBundles()
            {
                yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
                yield new \Teknoo\East\FoundationBundle\EastFoundationBundle();
                yield new \Teknoo\East\WebsiteBundle\TeknooEastWebsiteBundle();
            }

            protected function buildPHPDIContainer(DIContainerBuilder $builder)
            {
                $rootPath = \dirname(__DIR__, 2);
                $vendorPath = $rootPath . '/vendor';
                $builder->addDefinitions($vendorPath . '/teknoo/east-foundation/src/di.php');
                $builder->addDefinitions(
                    $vendorPath . '/teknoo/east-foundation/infrastructures/symfony/Resources/config/di.php'
                );
                $builder->addDefinitions($vendorPath . '/teknoo/east-website/src/di.php');
                $builder->addDefinitions($vendorPath . '/teknoo/east-website/infrastructures/doctrine/di.php');
                $builder->addDefinitions(
                    $vendorPath . '/teknoo/east-website/infrastructures/symfony/Resources/config/di.php'
                );
                $builder->addDefinitions($vendorPath . '/teknoo/east-website/infrastructures/di.php');
                $builder->addDefinitions($rootPath . '/src/di.php');
                $builder->addDefinitions($rootPath . '/infrastructures/Doctrine/di.php');
                $builder->addDefinitions($rootPath . '/infrastructures/Flysystem/di.php');
                $builder->addDefinitions($rootPath . '/infrastructures/Git/di.php');
                $builder->addDefinitions($rootPath . '/infrastructures/Kubernetes/di.php');
                $builder->addDefinitions($rootPath . '/infrastructures/Docker/di.php');
                $builder->addDefinitions($rootPath . '/infrastructures/Composer/di.php');
                $builder->addDefinitions($rootPath . '/infrastructures/Symfony/di.php');

                $builder->addDefinitions([
                    'teknoo_website_hostname' => 'localhost',
                    'teknoo.paas.worker.add_history_pattern' => function (): string {
                        return 'https://localhost/project/{projectId}/environment/{envName}/job/{jobId}/log';
                    },
                    'teknoo.paas.worker.global_variables' => [
                        'ROOT' => \dirname(__DIR__)
                    ],
                    'teknoo.paas.conductor.images_library' => [
                        'php-run-74' => [
                            'build-name' => 'php-run',
                            'tag' => '7.4',
                            'path' => '/library/php-run/7.4/',
                        ],
                    ],
                    UriFactoryInterface::class => get(UriFactory::class),
                    UriFactory::class => create(),

                    ResponseFactoryInterface::class => get(ResponseFactory::class),
                    ResponseFactory::class => create(),

                    RequestFactoryInterface::class => get(RequestFactory::class),
                    RequestFactory::class => create(),

                    StreamFactoryInterface::class => get(StreamFactory::class),
                    StreamFactory::class => create(),

                    //Misc
                    PsrClient::class => static function (): PsrClient {
                        return new class () implements PsrClient {
                            public function sendRequest(RequestInterface $request): ResponseInterface
                            {
                                return new \Laminas\Diactoros\Response();
                            }
                        };
                    },
                ]);

                $this->context->container = $builder->build();
                $this->context->container->set(ObjectManager::class, $this->context->buildObjectManager());
                return $this->context->container;
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
                $container->setParameter('kernel.project_dir', __DIR__);
            }

            protected function configureRoutes(RoutingConfigurator $routes)
            {
                $routes->import( __DIR__.'/config/routes/*.yaml','glob');
            }
        };

        $this->kernel->boot();
        $this->sfContainer = $this->kernel->getContainer();
        $rfo = new \ReflectionObject($this->kernel);
        $rfm = $rfo->getMethod('getPHPDIContainer');
        $rfm->setAccessible(true);
        $this->phpDiContainer = ($rfm->getClosure($this->kernel))();
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
            throw new \RuntimeException("Missing $className");
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

            public function findOneBy(array $criteria): ?object {
                $id = $criteria['id'];
                return ($this->getter)($this->className, $id);
            }
            public function getClassName(): string {}
        };

        return $this->repositories[$className];
    }

    /**
     * @Given I have a configured platform
     */
    public function iHaveAConfiguredPlatform()
    {
        $this->sfContainer->set(ObjectManager::class, $this->buildObjectManager());
        $this->buildRepository(Account::class);
        $this->buildRepository(BillingInformation::class);
        $this->buildRepository(Cluster::class);
        $this->buildRepository(Job::class);
        $this->buildRepository(PaymentInformation::class);
        $this->buildRepository(Project::class);
        $this->buildRepository(Content::class);
        $this->buildRepository(Item::class);
        $this->buildRepository(Media::class);
        $this->buildRepository(Type::class);
        $this->buildRepository(User::class);

        $this->sfContainer->get(DatesService::class)
            ->setCurrentDate(new \DateTime('2018-10-01 02:03:04', new \DateTimeZone('UTC')));
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
                ->setBillingInformation(new BillingInformation())
                ->setPaymentInformation(new PaymentInformation())
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
            $this->cluster = (new Cluster())->setId('cluster-id')->setProject($this->project)
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
        $this->project->setImagesRepository(
            $this->imagesRepository = (new DockerRepository(
                'foo',
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

        $request = Request::create('https://'.$this->sfContainer->get('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], [], $this->requestBody);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @When I call the PaaS with this PUT request :url with body :body and content type defined to :contentType
     */
    public function iCallThePaasWithThisPutRequestWithBodyAndContentTypeDefinedTo($url, $body, $contentType)
    {
        $this->calledUrl = $url;
        $this->requestBody = $body;

        $request = Request::create('https://'.$this->sfContainer->get('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], ['CONTENT_TYPE' => $contentType], $this->requestBody);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @When I push a new message :text at :date to :url
     */
    public function iPushANewMessageAtTo($text, $date, $url)
    {
        $this->calledUrl = $url;

        $body = \json_encode(new History(null, $this->historyMessage = $text, new \DateTime($this->historyDate = $date)));
        $request = Request::create('https://'.$this->sfContainer->get('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], [], $body);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @When I run a job :jobId from project :projectId to :arg2
     */
    public function iRunANewJobFromProjectAtTo($jobId, $projectId, $url)
    {
        $this->calledUrl = $url;

        $body = <<<EOF
{
  "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\Job",
  "id": "$jobId",
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
    "default_branch": "master",
    "identity": {
      "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\SshIdentity",
      "id": "df02b84131663568b62750e4d6b71922",
      "private_key": "fooBar"
    }
  },
  "images_repository": {
    "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\DockerRepository",
    "id": "530651c2cd6937158eaf11d36b8eeed4",
    "name": "fooBar",
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
      "identity": {
        "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\ClusterCredentials",
        "id": "f61d411e3f1b33eaa0900d3b17d36f1d",
        "name": "f61d411e3f1b33eaa0900d3b17d36f1d",
        "server_certificate": "fooBar",
        "private_key": "fooBar",
        "public_key": "fooBar"
      },
      "environment": {
        "@class": "Teknoo\\\\East\\\\Paas\\\\Object\\\\Environment",
        "name": "staging"
      }
    }
  ],
  "history": {
    "message": "teknoo.paas.jobs.configured",
    "date": "2020-08-26 09:13:55 UTC",
    "is_final": false,
    "extra": [],
    "previous": null
  },
  "variables": {
    "FOO": "foo"
  }
}
EOF;

        $request = Request::create('https://'.$this->sfContainer->get('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], [], $body);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @Then I must obtain an HTTP answer with this status code equals to :code.
     */
    public function iMustObtainAnHttpAnswerWithThisStatusCodeEqualsTo($code)
    {
        Assert::assertInstanceOf(Response::class, $this->response);
        Assert::assertEquals($code, $this->response->getStatusCode());
    }

    /**
     * @Then with this body answer, in json, :body
     */
    public function withThisBodyAnswerInJson($body)
    {
        $expected = \json_decode($body, true);
        $actual = \json_decode($this->response->getContent(), true);
        Assert::assertEquals($expected, $actual);
    }

    private function getNormalizedJob(array $variables = []): array
    {
        return [
            '@class' => OriJob::class,
            'id' => '',
            'project' => [
                '@class' => OriProject::class,
                'id' => $this->projectId,
                'name' => $this->projectName,
            ],
            'environment' => [
                '@class' => Environment::class,
                'name' => $this->envName,
            ],
            'source_repository' => [
                '@class' => GitRepository::class,
                'id' => 'git-id',
                'pull_url' => $this->repositoryUrl,
                'default_branch' => 'master',
                'identity' => null,
            ],
            'images_repository' => [
                '@class' => DockerRepository::class,
                'id' => '',
                'name' => 'foo',
                'api_url' => 'https://foo.bar',
                'identity' => null,
            ],
            'clusters' => [
                [
                    '@class' => Cluster::class,
                    'id' => 'cluster-id',
                    'name' => $this->clusterName,
                    'address' => '',
                    'identity' => null,
                    'environment' => [
                        '@class' => Environment::class,
                        'name' => $this->envName,
                    ],
                ],
            ],
            'history' => [
                'message' => 'teknoo.paas.jobs.configured',
                'date' => '2018-10-01 02:03:04 UTC',
                'is_final' => false,
                'extra' => [],
                'previous' => null,
            ],
            'variables' => $variables
        ];
    }

    /**
     * @Then with the job normalized in the body.
     */
    public function withTheJobNormalizedInTheBody()
    {
        $job = $this->getNormalizedJob([]);

        try {
            Assert::assertEquals(\json_encode($job), $this->response->getContent());
        } catch (ExpectationFailedException $error) {
            throw new \RuntimeException((string) $error, $error->getCode(), $error);
        }
    }

    /**
     * @Then with the job normalized in the body with variables :variables
     */
    public function withTheJobNormalizedInTheBodyWithVariables($variables)
    {
        $job = $this->getNormalizedJob(\json_decode($variables, true));

        try {
            Assert::assertEquals(\json_encode($job), $this->response->getContent());
        } catch (ExpectationFailedException $error) {
            throw new \RuntimeException((string) $error, $error->getCode(), $error);
        }
    }


    /**
     * @Given a job with the id :id at date :date
     */
    public function aJobWithTheIdAtDate($id, $date)
    {
        $this->jobId = $id;
        $this->jobDate = $date;

        $this->job = (new Job())->setId($this->jobId)
            ->setProject($this->project)
            ->setSourceRepository($this->sourceRepository)
            ->setClusters([$this->cluster])
            ->setEnvironment($this->environment)
            ->addToHistory('teknoo.paas.jobs.configured', new \DateTime($this->jobDate));

        $this->repositories[Job::class]->register(
            $id,
            $this->job
        );
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
            'previous' => new History(null, 'teknoo.paas.jobs.configured', new \DateTime($this->jobDate)),
        ];
        Assert::assertEquals(\json_encode($history), $this->response->getContent());
    }

    /**
     * @Then with the final history at date :date in the body
     */
    public function withTheFinalHistoryInTheBody($date)
    {
        $history = [
            'message' => PushResult::class,
            'date' => $this->historyDate = $date,
            'is_final' => true,
            'extra' => [],
            'previous' => null,
        ];
        Assert::assertEquals(\json_encode($history), $this->response->getContent());
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

#Custom image, not available in the library
images:
  foo:
    build-name: foo
    tag: lastest
    path: '/images/${FOO}'

#Hook to build the project before container, Called in this order
builds:
  composer-build: #Name of the step
    composer: install #Hook to call

#Volume to build to use with container
volumes:
  main: #Name of the module
    target: '/opt/foo/' #Path where data will be stored into the volume
    add: #folder or file, from .paas.yml where is located to add to the volume
      - 'src'
      - 'vendor'
      - 'composer.json'
      - 'composer.lock'
      - 'composer.phar'

#Pods (set of container)
pods:
  php-pods: #podset name
    replicas: 1 #instance of pods
    containers:
      php-run: #Container name
        image: php-run #Container image to use
        version: 7.4
        listen: #Port listen by the container
          - 8080
        volumes: #Volumes to link
          - main
        variables:
          SERVER_SCRIPT: '/opt/foo/src/server.php'

#Pods expositions
services:
  php-pods: #Pod name
    - listen: 9876 #Port listened
      target: 8080 #Pod's port targeted
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

            public function runInRoot(callable $callback): JobWorkspaceInterface
            {
                $callback('/foo');

                return $this;
            }

        };

        $this->phpDiContainer->set(
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

            public function cloningIntoPath(string $path): CloningAgentInterface
            {
                return $this;
            }
        };

        $this->phpDiContainer->set(
            CloningAgentInterface::class,
            $cloningAgent
        );
    }

    /**
     * @Given a composer hook as hook builder
     */
    public function aComposerHookAsHookBuilder()
    {
        $hook = new class implements HookInterface {
            public function setPath(string $path): HookInterface
            {
                return $this;
            }

            /**
             * @inheritDoc
             */
            public function setOptions(array $options): HookInterface
            {
                return $this;
            }

            public function run(): HookInterface
            {
                return $this;
            }
        };

        $collection = new class (['composer' => $hook]) implements HooksCollectionInterface {

            private iterable $hooks;

            public function __construct(iterable $hooks)
            {
                $this->hooks = $hooks;
            }

            public function getIterator(): \Traversable
            {
                yield from $this->hooks;
            }
        };

        $this->phpDiContainer->set(
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
            public function configure(string $url, ?IdentityInterface $auth): BuilderInterface
            {
                return clone $this;
            }

            public function buildImages(
                CompiledDeployment $compiledDeployment,
                PromiseInterface $promise
            ): BuilderInterface {
                $promise->success('foo');

                return $this;
            }

            public function buildVolumes(
                CompiledDeployment $compiledDeployment,
                string $workingPath,
                PromiseInterface $promise
            ): BuilderInterface {
                $promise->success('bar');

                return $this;
            }
        };

        $this->phpDiContainer->set(
            BuilderInterface::class,
            $builder
        );
    }

    /**
     * @Given a cluster client
     */
    public function aClusterClient()
    {
        $client = new class implements ClientInterface {
            public function configure(string $url, ?IdentityInterface $identity): ClientInterface
            {
                return clone $this;
            }

            public function deploy(CompiledDeployment $compiledDeployment, PromiseInterface $promise): ClientInterface
            {
                $promise->success(['foo' => 'bar']);

                return $this;
            }

            public function expose(CompiledDeployment $compiledDeployment, PromiseInterface $promise): ClientInterface
            {
                $promise->success(['foo' => 'bar']);
                return $this;
            }
        };

        $this->phpDiContainer->set(
            ClientInterface::class,
            $client
        );
    }
}
