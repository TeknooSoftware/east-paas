<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder as SfContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Item;
use Teknoo\East\Website\Doctrine\Object\Media;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Object\User;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Account;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\ImageRegistry;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Object\Job as OriJob;
use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
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
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Symfony\Component\DependencyInjection\Container;

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

            public function getProjectDir(): string
            {
                return \dirname(__DIR__, 2);
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
                yield new \Teknoo\East\Paas\Infrastructures\EastPaasBundle\TeknooEastPaasBundle();
                yield new \Teknoo\DI\SymfonyBridge\DIBridgeBundle();
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
                    $str .= $characters[\rand(0, \strlen($characters) - 1)];
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

            public function findOneBy(array $criteria, ?array $sort = null): ?object {
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
        $this->buildRepository(Cluster::class);
        $this->buildRepository(Job::class);
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
                ->setNamespace('behat-test')
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
            $this->cluster = (new Cluster())->setId('cluster-id')->setType('behat')->setProject($this->project)
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

        $body = \json_encode(new History(null, $this->historyMessage = $text, new \DateTime($this->historyDate = $date)));
        $request = Request::create('https://'.$this->sfContainer->getParameter('teknoo_website_hostname').$this->calledUrl, 'PUT', [], [], [], [], $body);
        $this->response = $this->kernel->handle($request);
    }

    /**
     * @When I run a job :jobId from project :projectId to :url
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
      "type": "behat",
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
        $expected = \json_decode($body, true);
        $actual = \json_decode($current = $this->response->getContent(), true);
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
            'base_namespace' => 'behat-test',
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
                    'type' => 'behat',
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

        $content = \json_decode($this->response->getContent(), true);
        try {
            Assert::assertEquals(
                $job,
                $content
            );
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

        $content = \json_decode($this->response->getContent(), true);

        try {
            Assert::assertEquals($job, $content);
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
            ->setExtra(['foo' => 'bar'])
            ->addToHistory('teknoo.east.paas.jobs.configured', new \DateTime($this->jobDate));

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
            'previous' => new History(null, 'teknoo.east.paas.jobs.configured', new \DateTime($this->jobDate)),
        ];
        Assert::assertEquals(\json_encode($history), $this->response->getContent());
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
        ];

        $content = \json_decode($this->response->getContent(), true);

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
  namespace: 'demo'

#Secrets provider
secrets:
  map_vault:
    provider: map #Internal secrets, must be passed in this file
    options:
      key1: value1
      key2: ${FOO}
  volume_vault:
    provider: map
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
    composer: install #Hook to call

#Volume to build to use with container
volumes:
  extra: #Name of the volume
    local-path: "/foo/bar" #optional local path where store data in the volume
    add: #folder or file, from .paas.yml where is located to add to the volume
      - 'extra'
  other_name: #Name of the volume
    add: #folder or file, from .paas.yml where is located to add to the volume
      - 'vendor'

#Pods (set of container)
pods:
  php-pods: #podset name
    replicas: 2 #instance of pods
    containers:
      php-run: #Container name
        image: registry.teknoo.io/php-run #Container image to use
        version: 7.4
        listen: #Port listen by the container
          - 8080
        volumes: #Volumes to link
          extra:
            from: 'extra'
            mount-path: '/opt/extra' #Path where volume will be mount
          app:
            mount-path: '/opt/app' #Path where data will be stored
            add: #folder or file, from .paas.yml where is located to add to the volume
              - 'src'
              - 'vendor'
              - 'composer.json'
              - 'composer.lock'
              - 'composer.phar'
          data: #Persistent volume, can not be pre-populated
            mount-path: '/opt/data'
            persistent: true
          vault:
            mount-path: '/vault'
            from-secret: 'volume_vault'
        variables: #To define some environment variables
          SERVER_SCRIPT: '/opt/app/src/server.php'
          from-secrets: #To fetch some value from secret/vault
            KEY1: 'map_vault.key1'
  demo-pods:
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
  demo-service: #Service name
    pod: "demo-pods" #Pod name, use service name by default
    ports:
      - listen: 8080 #Port listened
        target: 8080 #Pod's port targeted

#Ingresses configuration
ingresses:
  demo: #rule name
    host: demo-paas.teknoo.io
    tls:
      secret: "demo_vault" #Configure the orchestrator to fetch value from vault
    service: #default service
      name: demo-service
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

            public function runInRoot(callable $callback): JobWorkspaceInterface
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

            public function cloningIntoPath(string $path): CloningAgentInterface
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
        $hook = new class implements HookInterface {
            public function setPath(string $path): HookInterface
            {
                return $this;
            }

            /**
             * @inheritDoc
             */
            public function setOptions(array $options, PromiseInterface $promise): HookInterface
            {
                $promise->success();
                return $this;
            }

            public function run(PromiseInterface $promise): HookInterface
            {
                $promise->success('foo');
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
}
