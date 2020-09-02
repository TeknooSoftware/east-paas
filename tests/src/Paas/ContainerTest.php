<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Teknoo\East\Website\DBSource\ManagerInterface;
use Teknoo\East\Website\Service\DeletingService;
use Teknoo\East\Paas\Conductor\Conductor;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\AccountRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\BillingInformationRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ClusterRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\PaymentInformationRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
use Teknoo\East\Paas\Loader\AccountLoader;
use Teknoo\East\Paas\Loader\BillingInformationLoader;
use Teknoo\East\Paas\Loader\ClusterLoader;
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Loader\PaymentInformationLoader;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Contracts\Recipe\AddHistoryRecipe;
use Teknoo\East\Paas\Contracts\Recipe\NewJobRecipe;
use Teknoo\East\Paas\Contracts\Recipe\RunJobRecipe;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildImages;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildVolumes;
use Teknoo\East\Paas\Recipe\Step\Worker\CloneRepository;
use Teknoo\East\Paas\Recipe\Step\Worker\CompileDeployment;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureConductor;
use Teknoo\East\Paas\Recipe\Step\Job\CreateNewJob;
use Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory;
use Teknoo\East\Paas\Recipe\Step\Job\DeserializeJob;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureImagesBuilder;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureClusterClient;
use Teknoo\East\Paas\Recipe\Step\Worker\Deploying;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
use Teknoo\East\Paas\Recipe\Step\History\DisplayHistory;
use Teknoo\East\Paas\Recipe\Step\Job\DisplayJob;
use Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\East\Paas\Recipe\Step\Worker\Exposing;
use Teknoo\East\Paas\Recipe\Step\Worker\HookBuildContainer;
use Teknoo\East\Paas\Recipe\Step\Job\PrepareJob;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Recipe\Step\Misc\PushResult;
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Recipe\Step\History\SerializeHistory;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Writer\AccountWriter;
use Teknoo\East\Paas\Writer\BillingInformationWriter;
use Teknoo\East\Paas\Writer\ClusterWriter;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\East\Paas\Writer\PaymentInformationWriter;
use Teknoo\East\Paas\Writer\ProjectWriter;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Recipe;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildImage() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../src/di.php');
        $containerDefinition->addDefinitions([
            'teknoo.east.paas.worker.add_history_pattern' => 'foo',
        ]);

        return $containerDefinition->build();
    }

    private function generateTestForLoader(string $className, string $repositoryClass)
    {
        $container = $this->buildImage();
        $manager = $this->createMock(ManagerInterface::class);
        $repository = $this->createMock($repositoryClass);

        $container->set(ManagerInterface::class, $manager);
        $container->set($repositoryClass, $repository);
        
        $loader = null;
        try {
            $loader = $container->get($className);
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertInstanceOf(
            $className,
            $loader
        );
    }

    public function testAccountLoader()
    {
        $this->generateTestForLoader(AccountLoader::class, AccountRepositoryInterface::class);
    }

    public function testBillingInformationLoader()
    {
        $this->generateTestForLoader(BillingInformationLoader::class, BillingInformationRepositoryInterface::class);
    }

    public function testJobLoader()
    {
        $this->generateTestForLoader(JobLoader::class, JobRepositoryInterface::class);
    }

    public function testPaymentInformationLoader()
    {
        $this->generateTestForLoader(PaymentInformationLoader::class, PaymentInformationRepositoryInterface::class);
    }

    public function testProjectLoader()
    {
        $this->generateTestForLoader(ProjectLoader::class, ProjectRepositoryInterface::class);
    }

    public function testClusterLoader()
    {
        $this->generateTestForLoader(ClusterLoader::class, ClusterRepositoryInterface::class);
    }

    private function generateTestForWriter(string $className)
    {
        $container = $this->buildImage();
        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $loader = null;
        try {
            $loader = $container->get($className);
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertInstanceOf(
            $className,
            $loader
        );
    }

    public function testAccountWriter()
    {
        $this->generateTestForWriter(AccountWriter::class);
    }

    public function testBillingInformationWriter()
    {
        $this->generateTestForWriter(BillingInformationWriter::class);
    }

    public function testJobWriter()
    {
        $this->generateTestForWriter(JobWriter::class);
    }

    public function testPaymentInformationWriter()
    {
        $this->generateTestForWriter(PaymentInformationWriter::class);
    }

    public function testProjectWriter()
    {
        $this->generateTestForWriter(ProjectWriter::class);
    }

    public function testClusterWriter()
    {
        $this->generateTestForWriter(ClusterWriter::class);
    }

    private function generateTestForDelete(string $key)
    {
        $container = $this->buildImage();
        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $loader = null;
        try {
            $loader = $container->get($key);
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertInstanceOf(
            DeletingService::class,
            $loader
        );
    }

    public function testAccountDelete()
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.account');
    }

    public function testBillingInformationDelete()
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.billing_information');
    }

    public function testPaymentInformationDelete()
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.payment_information');
    }

    public function testProjectDelete()
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.project');
    }

    public function testJobDelete()
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.job');
    }

    public function testClusterDelete()
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.cluster');
    }

    public function testAddHistory()
    {
        $container = $this->buildImage();

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));

        self::assertInstanceOf(
            AddHistory::class,
            $container->get(AddHistory::class)
        );
    }

    public function testBuildImages()
    {
        $container = $this->buildImage();
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        self::assertInstanceOf(
            BuildImages::class,
            $container->get(BuildImages::class)
        );
    }

    public function testBuildVolumes()
    {
        $container = $this->buildImage();
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        self::assertInstanceOf(
            BuildVolumes::class,
            $container->get(BuildVolumes::class)
        );
    }

    public function testCloneRepository()
    {
        $container = $this->buildImage();

        self::assertInstanceOf(
            CloneRepository::class,
            $container->get(CloneRepository::class)
        );
    }

    public function testCompileDeployment()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            CompileDeployment::class,
            $container->get(CompileDeployment::class)
        );
    }

    public function testConfigureCloningAgent()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        $container->set(CloningAgentInterface::class, $this->createMock(CloningAgentInterface::class));

        self::assertInstanceOf(
            ConfigureCloningAgent::class,
            $container->get(ConfigureCloningAgent::class)
        );
    }

    public function testConfigureConductor()
    {
        $container = $this->buildImage();

        $container->set(ConductorInterface::class, $this->createMock(ConductorInterface::class));

        self::assertInstanceOf(
            ConfigureConductor::class,
            $container->get(ConfigureConductor::class)
        );
    }

    public function testCreateNewJob()
    {
        $container = $this->buildImage();
        self::assertInstanceOf(
            CreateNewJob::class,
            $container->get(CreateNewJob::class)
        );
    }

    public function testDeserializeHistory()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));

        self::assertInstanceOf(
            DeserializeHistory::class,
            $container->get(DeserializeHistory::class)
        );
    }

    public function testDeserializeJob()
    {
        $container = $this->buildImage();

        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));
        $container->set('teknoo.east.paas.worker.global_variables', ['foo' => 'bar']);
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            DeserializeJob::class,
            $container->get(DeserializeJob::class)
        );
    }

    public function testConfigureImagesBuilder()
    {
        $container = $this->buildImage();
        $container->set(BuilderInterface::class, $this->createMock(BuilderInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            ConfigureImagesBuilder::class,
            $container->get(ConfigureImagesBuilder::class)
        );
    }

    public function testConfigureClusterClient()
    {
        $container = $this->buildImage();
        $container->set(ClusterClientInterface::class, $this->createMock(ClusterClientInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            ConfigureClusterClient::class,
            $container->get(ConfigureClusterClient::class)
        );
    }

    public function testDeploying()
    {
        $container = $this->buildImage();
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        self::assertInstanceOf(
            Deploying::class,
            $container->get(Deploying::class)
        );
    }

    public function testDisplayError()
    {
        $container = $this->buildImage();

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            DisplayError::class,
            $container->get(DisplayError::class)
        );
    }

    public function testDisplayHistory()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            DisplayHistory::class,
            $container->get(DisplayHistory::class)
        );
    }

    public function testDisplayJob()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            DisplayJob::class,
            $container->get(DisplayJob::class)
        );
    }

    public function testGetEnvironment()
    {
        $container = $this->buildImage();
        self::assertInstanceOf(
            GetEnvironment::class,
            $container->get(GetEnvironment::class)
        );
    }


    public function testGetJob()
    {
        $container = $this->buildImage();
        $container->set(JobRepositoryInterface::class, $this->createMock(JobRepositoryInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            GetJob::class,
            $container->get(GetJob::class)
        );
    }

    public function testGetProject()
    {
        $container = $this->buildImage();

        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $container->set(ProjectRepositoryInterface::class, $this->createMock(ProjectRepositoryInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            GetProject::class,
            $container->get(GetProject::class)
        );
    }

    public function testExposing()
    {
        $container = $this->buildImage();
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        self::assertInstanceOf(
            Exposing::class,
            $container->get(Exposing::class)
        );
    }

    public function testHookBuildContainer()
    {
        $container = $this->buildImage();
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        self::assertInstanceOf(
            HookBuildContainer::class,
            $container->get(HookBuildContainer::class)
        );
    }

    public function testPrepareJob()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            PrepareJob::class,
            $container->get(PrepareJob::class)
        );
    }

    public function testPrepareWorkspace()
    {
        $container = $this->buildImage();

        $container->set(JobWorkspaceInterface::class, $this->createMock(JobWorkspaceInterface::class));

        self::assertInstanceOf(
            PrepareWorkspace::class,
            $container->get(PrepareWorkspace::class)
        );
    }

    public function testPushResult()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        $container->set(NormalizerInterface::class, $this->createMock(NormalizerInterface::class));

        self::assertInstanceOf(
            PushResult::class,
            $container->get(PushResult::class)
        );
    }

    public function testReceiveHistory()
    {
        $container = $this->buildImage();
        self::assertInstanceOf(
            ReceiveHistory::class,
            $container->get(ReceiveHistory::class)
        );
    }

    public function testReceiveJob()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            ReceiveJob::class,
            $container->get(ReceiveJob::class)
        );
    }

    public function testReadDeploymentConfiguration()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            ReadDeploymentConfiguration::class,
            $container->get(ReadDeploymentConfiguration::class)
        );
    }

    public function testSaveJob()
    {
        $container = $this->buildImage();
        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);

        self::assertInstanceOf(
            SaveJob::class,
            $container->get(SaveJob::class)
        );
    }

    public function testSendHistory()
    {
        $container = $this->buildImage();
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        $container->set('serializer', $this->createMock(SerializerInterface::class));

        self::assertInstanceOf(
            SendHistory::class,
            $container->get(SendHistory::class)
        );
    }

    public function testSerializeHistory()
    {
        $container = $this->buildImage();

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            SerializeHistory::class,
            $container->get(SerializeHistory::class)
        );
    }

    public function testSerializeJob()
    {
        $container = $this->buildImage();

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            SerializeJob::class,
            $container->get(SerializeJob::class)
        );
    }

    public function testNewJobRecipe()
    {
        $container = $this->buildImage();

        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $container->set(JobRepositoryInterface::class, $this->createMock(JobRepositoryInterface::class));
        $container->set(ProjectRepositoryInterface::class, $this->createMock(ProjectRepositoryInterface::class));

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));
        $container->set(NormalizerInterface::class, $this->createMock(NormalizerInterface::class));
        $container->set(DispatchJobInterface::class, $this->createMock(DispatchJobInterface::class));

        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            Recipe::class,
            $container->get(NewJobRecipe::class)
        );
    }

    public function testAddHistoryRecipe()
    {
        $container = $this->buildImage();

        $manager = $this->createMock(ManagerInterface::class);
        
        $container->set(ManagerInterface::class, $manager);
        $container->set(JobRepositoryInterface::class, $this->createMock(JobRepositoryInterface::class));
        $container->set(ProjectRepositoryInterface::class, $this->createMock(ProjectRepositoryInterface::class));

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));
        $container->set(NormalizerInterface::class, $this->createMock(NormalizerInterface::class));

        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        self::assertInstanceOf(
            Recipe::class,
            $container->get(AddHistoryRecipe::class)
        );

        $recipe = $container->get(AddHistoryRecipe::class);

        $chef = $this->createMock(ChefInterface::class);
        $recipe = $recipe->train($chef);

        $workplan = ['date' => '2018-05-01 00:00:00 +0000'];
        $recipe->prepare($workplan, $chef);
    }

    public function testRunJobRecipe()
    {
        $container = $this->buildImage();

        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $container->set(JobRepositoryInterface::class, $this->createMock(JobRepositoryInterface::class));
        $container->set(ConductorInterface::class, $this->createMock(ConductorInterface::class));
        $container->set(CloningAgentInterface::class, $this->createMock(CloningAgentInterface::class));
        $container->set(JobWorkspaceInterface::class, $this->createMock(JobWorkspaceInterface::class));
        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));
        $container->set(NormalizerInterface::class, $this->createMock(NormalizerInterface::class));
        $container->set(BuilderInterface::class, $this->createMock(BuilderInterface::class));
        $container->set(ClusterClientInterface::class, $this->createMock(ClusterClientInterface::class));
        $container->set('teknoo.east.paas.worker.global_variables', ['foo' => 'bar']);
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        self::assertInstanceOf(
            Recipe::class,
            $container->get(RunJobRecipe::class)
        );

        $recipe = $container->get(RunJobRecipe::class);

        $chef = $this->createMock(ChefInterface::class);
        $recipe->train($chef);
    }

    public function testConductor()
    {
        $container = $this->buildImage();

        $container->set(PropertyAccessorInterface::class, $this->createMock(PropertyAccessorInterface::class));
        $container->set(YamlParserInterface::class, $this->createMock(YamlParserInterface::class));
        $container->set(HooksCollectionInterface::class, $this->createMock(HooksCollectionInterface::class));
        $container->set('teknoo.east.paas.root_dir', '/foo');

        $container->set(
            'teknoo.east.paas.conductor.images_library',
            [
                'php-react-74' => [
                    'build-name' => 'php-react',
                    'tag' => '7.4',
                    'path' => '/library/php-react/7.4/',
                ],
                'php-fpm-74' => [
                    'build-name' => 'php-fpm',
                    'tag' => '7.4',
                    'path' => '/library/php-fpm/7.4/',
                ],
            ]
        );

        self::assertInstanceOf(
            Conductor::class,
            $container->get(Conductor::class)
        );

        self::assertInstanceOf(
            Conductor::class,
            $container->get(ConductorInterface::class)
        );
    }

    public function testConductorWithBadLibrary()
    {
        $container = $this->buildImage();

        $container->set(PropertyAccessorInterface::class, $this->createMock(PropertyAccessorInterface::class));
        $container->set(YamlParserInterface::class, $this->createMock(YamlParserInterface::class));
        $container->set(HooksCollectionInterface::class, $this->createMock(HooksCollectionInterface::class));
        $container->set('teknoo.east.paas.root_dir', '/foo');

        $container->set(
            'teknoo.east.paas.conductor.images_library',
            [
                'php-react-74' => [
                    'build-name' => 'php-react',
                    'tag' => '7.4',
                ],
            ]
        );

        $this->expectException(\RuntimeException::class);
        $container->get(Conductor::class);
    }
}
