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

namespace Teknoo\Tests\East\Paas;

use ArrayObject;
use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use stdClass;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Teknoo\East\Foundation\Liveness\PingServiceInterface;
use Teknoo\East\Foundation\Liveness\TimeoutServiceInterface;
use Teknoo\East\Foundation\Recipe\RecipeInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirementCompiler;
use Teknoo\East\Paas\Compilation\Compiler\HookCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ImageCompiler;
use Teknoo\East\Paas\Compilation\Compiler\IngressCompiler;
use Teknoo\East\Paas\Compilation\Compiler\JobCompiler;
use Teknoo\East\Paas\Compilation\Compiler\MapCompiler;
use Teknoo\East\Paas\Compilation\Compiler\PodCompiler;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as QuotaFactory;
use Teknoo\East\Paas\Compilation\Compiler\QuotaCompiler;
use Teknoo\East\Paas\Compilation\Compiler\SecretCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ServiceCompiler;
use Teknoo\East\Paas\Compilation\Compiler\VolumeCompiler;
use Teknoo\East\Paas\Compilation\CompiledDeploymentFactory;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerCollectionInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\AddHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\EditAccountEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\EditProjectEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\NewJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\RunJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\DI\Exception\InvalidArgumentException;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Parser\YamlValidator;
use Teknoo\East\Paas\Recipe\Plan\NewAccountEndPoint;
use Teknoo\East\Paas\Recipe\Plan\NewProjectEndPoint;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Contracts\DBSource\ManagerInterface;
use Teknoo\East\Common\Recipe\Step\CreateObject;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\East\Common\Service\DeletingService;
use Teknoo\East\Paas\Compilation\Conductor;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\AccountRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ClusterRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
use Teknoo\East\Paas\Loader\AccountLoader;
use Teknoo\East\Paas\Loader\ClusterLoader;
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory;
use Teknoo\East\Paas\Recipe\Step\Misc\Ping;
use Teknoo\East\Paas\Recipe\Step\Misc\SetTimeLimit;
use Teknoo\East\Paas\Recipe\Step\Misc\UnsetTimeLimit;
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
use Teknoo\East\Paas\Recipe\Step\Project\GetEnvironment;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\East\Paas\Recipe\Step\Worker\Exposing;
use Teknoo\East\Paas\Recipe\Step\Worker\HookingDeployment;
use Teknoo\East\Paas\Recipe\Step\Job\PrepareJob;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Writer\AccountWriter;
use Teknoo\East\Paas\Writer\ClusterWriter;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\East\Paas\Writer\ProjectWriter;
use Teknoo\Recipe\Bowl\BowlInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\PlanInterface;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    /**
     * @throws \Exception
     */
    protected function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../src/di.php');
        $containerDefinition->addDefinitions([
            'teknoo.east.paas.worker.add_history_pattern' => 'foo',
        ]);

        return $containerDefinition->build();
    }

    private function generateTestForLoader(string $className, string $repositoryClass): void
    {
        $container = $this->buildContainer();
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

        $this->assertInstanceOf($className, $loader);
    }

    public function testDirectory(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(Directory::class, $container->get(Directory::class));
    }

    public function testAccountLoader(): void
    {
        $this->generateTestForLoader(AccountLoader::class, AccountRepositoryInterface::class);
    }

    public function testJobLoader(): void
    {
        $this->generateTestForLoader(JobLoader::class, JobRepositoryInterface::class);
    }

    public function testProjectLoader(): void
    {
        $this->generateTestForLoader(ProjectLoader::class, ProjectRepositoryInterface::class);
    }

    public function testClusterLoader(): void
    {
        $this->generateTestForLoader(ClusterLoader::class, ClusterRepositoryInterface::class);
    }

    private function generateTestForWriter(string $className): void
    {
        $container = $this->buildContainer();
        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $loader = null;
        try {
            $loader = $container->get($className);
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        $this->assertInstanceOf($className, $loader);
    }

    public function testAccountWriter(): void
    {
        $this->generateTestForWriter(AccountWriter::class);
    }

    public function testJobWriter(): void
    {
        $this->generateTestForWriter(JobWriter::class);
    }

    public function testProjectWriter(): void
    {
        $this->generateTestForWriter(ProjectWriter::class);
    }

    public function testClusterWriter(): void
    {
        $this->generateTestForWriter(ClusterWriter::class);
    }

    private function generateTestForDelete(string $key): void
    {
        $container = $this->buildContainer();
        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $loader = null;
        try {
            $loader = $container->get($key);
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        $this->assertInstanceOf(DeletingService::class, $loader);
    }

    public function testAccountDelete(): void
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.account');
    }

    public function testProjectDelete(): void
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.project');
    }

    public function testJobDelete(): void
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.job');
    }

    public function testClusterDelete(): void
    {
        $this->generateTestForDelete('teknoo.east.paas.deleting.cluster');
    }

    public function testAddHistory(): void
    {
        $container = $this->buildContainer();

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));

        $this->assertInstanceOf(AddHistory::class, $container->get(AddHistory::class));
    }

    public function testBuildImages(): void
    {
        $container = $this->buildContainer();
        $container->set(DispatchHistoryInterface::class, $this->createMock(DispatchHistoryInterface::class));
        $container->set(MessageFactoryInterface::class, $this->createMock(MessageFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(BuildImages::class, $container->get(BuildImages::class));
    }

    public function testBuildVolumes(): void
    {
        $container = $this->buildContainer();
        $container->set(DispatchHistoryInterface::class, $this->createMock(DispatchHistoryInterface::class));
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(MessageFactoryInterface::class, $this->createMock(MessageFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(BuildVolumes::class, $container->get(BuildVolumes::class));
    }

    public function testCloneRepository(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(CloneRepository::class, $container->get(CloneRepository::class));
    }

    public function testCompileDeployment(): void
    {
        $container = $this->buildContainer();
        $container->set(MessageFactoryInterface::class, $this->createMock(MessageFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(CompileDeployment::class, $container->get(CompileDeployment::class));
    }

    public function testConfigureCloningAgent(): void
    {
        $container = $this->buildContainer();
        $container->set(MessageFactoryInterface::class, $this->createMock(MessageFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(CloningAgentInterface::class, $this->createMock(CloningAgentInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(ConfigureCloningAgent::class, $container->get(ConfigureCloningAgent::class));
    }

    public function testConfigureConductor(): void
    {
        $container = $this->buildContainer();

        $container->set(ConductorInterface::class, $this->createMock(ConductorInterface::class));

        $this->assertInstanceOf(ConfigureConductor::class, $container->get(ConfigureConductor::class));
    }

    public function testCreateNewJob(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(CreateNewJob::class, $container->get(CreateNewJob::class));
    }

    public function testDeserializeHistory(): void
    {
        $container = $this->buildContainer();
        $container->set(MessageFactoryInterface::class, $this->createMock(MessageFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(DeserializeHistory::class, $container->get(DeserializeHistory::class));
    }

    public function testDeserializeJob(): void
    {
        $container = $this->buildContainer();

        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));
        $container->set('teknoo.east.paas.compilation.global_variables', ['foo' => 'bar']);
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(DeserializeJob::class, $container->get(DeserializeJob::class));
    }

    public function testConfigureImagesBuilder(): void
    {
        $container = $this->buildContainer();
        $container->set(BuilderInterface::class, $this->createMock(BuilderInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(ConfigureImagesBuilder::class, $container->get(ConfigureImagesBuilder::class));
    }

    public function testConfigureClusterClient(): void
    {
        $container = $this->buildContainer();
        $container->set(Directory::class, $this->createMock(Directory::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(ConfigureClusterClient::class, $container->get(ConfigureClusterClient::class));
    }

    public function testDeploying(): void
    {
        $container = $this->buildContainer();
        $container->set(DispatchHistoryInterface::class, $this->createMock(DispatchHistoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(Deploying::class, $container->get(Deploying::class));
    }

    public function testGetEnvironment(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(GetEnvironment::class, $container->get(GetEnvironment::class));
    }

    public function testGetJob(): void
    {
        $container = $this->buildContainer();
        $container->set(JobRepositoryInterface::class, $this->createMock(JobRepositoryInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(GetJob::class, $container->get(GetJob::class));
    }

    public function testGetProject(): void
    {
        $container = $this->buildContainer();

        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $container->set(ProjectRepositoryInterface::class, $this->createMock(ProjectRepositoryInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(GetProject::class, $container->get(GetProject::class));
    }

    public function testExposing(): void
    {
        $container = $this->buildContainer();
        $container->set(DispatchHistoryInterface::class, $this->createMock(DispatchHistoryInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        $this->assertInstanceOf(Exposing::class, $container->get(Exposing::class));
    }

    public function testHookingDeployment(): void
    {
        $container = $this->buildContainer();
        $container->set(DispatchHistoryInterface::class, $this->createMock(DispatchHistoryInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));

        $this->assertInstanceOf(HookingDeployment::class, $container->get(HookingDeployment::class));
    }

    public function testPrepareJob(): void
    {
        $container = $this->buildContainer();
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(PrepareJob::class, $container->get(PrepareJob::class));
    }

    public function testPrepareJobNotpreferRealDate(): void
    {
        $container = $this->buildContainer();
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));
        $container->set('teknoo.east.paas.symfony.prepare-job.prefer-real-date', false);

        $this->assertInstanceOf(PrepareJob::class, $container->get(PrepareJob::class));
    }

    public function testPrepareWorkspace(): void
    {
        $container = $this->buildContainer();

        $container->set(JobWorkspaceInterface::class, $this->createMock(JobWorkspaceInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(PrepareWorkspace::class, $container->get(PrepareWorkspace::class));
    }

    public function testReceiveHistory(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(ReceiveHistory::class, $container->get(ReceiveHistory::class));
    }

    public function testReceiveJob(): void
    {
        $container = $this->buildContainer();
        $container->set(MessageFactoryInterface::class, $this->createMock(MessageFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));

        $this->assertInstanceOf(ReceiveJob::class, $container->get(ReceiveJob::class));
    }

    public function testReadDeploymentConfiguration(): void
    {
        $container = $this->buildContainer();
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(ReadDeploymentConfiguration::class, $container->get(ReadDeploymentConfiguration::class));
    }

    public function testSaveJob(): void
    {
        $container = $this->buildContainer();
        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(SaveJob::class, $container->get(SaveJob::class));
    }

    public function testSerialGenerator(): void
    {
        $container = $this->buildContainer();
        $this->assertInstanceOf(SerialGenerator::class, $container->get(SerialGenerator::class));
    }

    public function testSerializeJob(): void
    {
        $container = $this->buildContainer();

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(SerializeJob::class, $container->get(SerializeJob::class));
    }

    public function testNewAccountEndPoint(): void
    {
        $container = $this->buildContainer();

        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(CreateObject::class, $this->createMock(CreateObject::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RedirectClientInterface::class, $this->createMock(RedirectClientInterface::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));
        $container->set(
            ObjectAccessControlInterface::class,
            $this->createMock(ObjectAccessControlInterface::class)
        );
        $container->set(
            'teknoo.east.common.get_default_error_template',
            'foo.template',
        );

        $this->assertInstanceOf(PlanInterface::class, $container->get(NewAccountEndPoint::class));
    }

    public function testNewProjectEndPoint(): void
    {
        $container = $this->buildContainer();

        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(CreateObject::class, $this->createMock(CreateObject::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RedirectClientInterface::class, $this->createMock(RedirectClientInterface::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));
        $container->set(
            'teknoo.east.common.get_default_error_template',
            'foo.template',
        );

        $this->assertInstanceOf(PlanInterface::class, $container->get(NewProjectEndPoint::class));
    }

    public function testNewProjectEndPointWithAccessControl(): void
    {
        $container = $this->buildContainer();

        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(CreateObject::class, $this->createMock(CreateObject::class));
        $container->set(ObjectAccessControlInterface::class, $this->createMock(ObjectAccessControlInterface::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RedirectClientInterface::class, $this->createMock(RedirectClientInterface::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));

        $container->set(
            'teknoo.east.common.get_default_error_template',
            'foo.template',
        );

        $this->assertInstanceOf(PlanInterface::class, $container->get(NewProjectEndPoint::class));
    }

    public function testEditAccountEndPointInterface(): void
    {
        $container = $this->buildContainer();

        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadObject::class, $this->createMock(LoadObject::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));

        $container->set(
            'teknoo.east.common.get_default_error_template',
            'foo.template',
        );

        $this->assertInstanceOf(PlanInterface::class, $container->get(EditAccountEndPointInterface::class));
    }

    public function testEditAccountEndPointInterfaceWithAccessControl(): void
    {
        $container = $this->buildContainer();

        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadObject::class, $this->createMock(LoadObject::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));

        $container->set(ObjectAccessControlInterface::class, $this->createMock(ObjectAccessControlInterface::class));

        $container->set(
            'teknoo.east.common.get_default_error_template',
            'foo.template',
        );

        $this->assertInstanceOf(PlanInterface::class, $container->get(EditAccountEndPointInterface::class));
    }

    public function testEditProjectEndPointInterface(): void
    {
        $container = $this->buildContainer();

        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadObject::class, $this->createMock(LoadObject::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));

        $container->set(
            'teknoo.east.common.get_default_error_template',
            'foo.template',
        );

        $this->assertInstanceOf(PlanInterface::class, $container->get(EditProjectEndPointInterface::class));
    }

    public function testEditProjectEndPointInterfaceWithAccessControl(): void
    {
        $container = $this->buildContainer();

        $container->set(OriginalRecipeInterface::class, $this->createMock(OriginalRecipeInterface::class));
        $container->set(LoadObject::class, $this->createMock(LoadObject::class));
        $container->set(FormHandlingInterface::class, $this->createMock(FormHandlingInterface::class));
        $container->set(FormProcessingInterface::class, $this->createMock(FormProcessingInterface::class));
        $container->set(SaveObject::class, $this->createMock(SaveObject::class));
        $container->set(RenderFormInterface::class, $this->createMock(RenderFormInterface::class));
        $container->set(RenderError::class, $this->createMock(RenderError::class));

        $container->set(ObjectAccessControlInterface::class, $this->createMock(ObjectAccessControlInterface::class));

        $container->set(
            'teknoo.east.common.get_default_error_template',
            'foo.template',
        );

        $this->assertInstanceOf(PlanInterface::class, $container->get(EditProjectEndPointInterface::class));
    }

    public function testNewJob(): void
    {
        $container = $this->buildContainer();

        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $container->set(JobRepositoryInterface::class, $this->createMock(JobRepositoryInterface::class));
        $container->set(ProjectRepositoryInterface::class, $this->createMock(ProjectRepositoryInterface::class));

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));
        $container->set(NormalizerInterface::class, $this->createMock(NormalizerInterface::class));
        $container->set(DispatchJobInterface::class, $this->createMock(DispatchJobInterface::class));

        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));
        $container->set(DispatchResultInterface::class, $this->createMock(DispatchResultInterface::class));
        $container->set(SendJobInterface::class, $this->createMock(SendJobInterface::class));
        $container->set(PingServiceInterface::class, $this->createMock(PingServiceInterface::class));
        $container->set(TimeoutServiceInterface::class, $this->createMock(TimeoutServiceInterface::class));

        $this->assertInstanceOf(PlanInterface::class, $container->get(NewJobInterface::class));
    }

    public function testAddHistoryPlan(): void
    {
        $container = $this->buildContainer();

        $manager = $this->createMock(ManagerInterface::class);

        $container->set(ManagerInterface::class, $manager);
        $container->set(JobRepositoryInterface::class, $this->createMock(JobRepositoryInterface::class));
        $container->set(ProjectRepositoryInterface::class, $this->createMock(ProjectRepositoryInterface::class));

        $container->set(SerializerInterface::class, $this->createMock(SerializerInterface::class));
        $container->set(DeserializerInterface::class, $this->createMock(DeserializerInterface::class));
        $container->set(NormalizerInterface::class, $this->createMock(NormalizerInterface::class));

        $container->set(DispatchResultInterface::class, $this->createMock(DispatchResultInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));
        $container->set(SendJobInterface::class, $this->createMock(SendJobInterface::class));
        $container->set(SendHistoryInterface::class, $this->createMock(SendHistoryInterface::class));
        $container->set(PingServiceInterface::class, $this->createMock(PingServiceInterface::class));
        $container->set(TimeoutServiceInterface::class, $this->createMock(TimeoutServiceInterface::class));

        $this->assertInstanceOf(PlanInterface::class, $container->get(AddHistoryInterface::class));
    }

    public function testRunJob(): void
    {
        $container = $this->buildContainer();

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
        $container->set(Directory::class, $this->createMock(Directory::class));
        $container->set('teknoo.east.paas.compilation.global_variables', ['foo' => 'bar']);
        $container->set(MessageFactoryInterface::class, $this->createMock(MessageFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(UriFactoryInterface::class, $this->createMock(UriFactoryInterface::class));
        $container->set(RequestFactoryInterface::class, $this->createMock(RequestFactoryInterface::class));
        $container->set(ClientInterface::class, $this->createMock(ClientInterface::class));
        $container->set(DispatchHistoryInterface::class, $this->createMock(DispatchHistoryInterface::class));
        $container->set(DispatchResultInterface::class, $this->createMock(DispatchResultInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));
        $container->set(SendHistoryInterface::class, $this->createMock(SendHistoryInterface::class));
        $container->set(PingServiceInterface::class, $this->createMock(PingServiceInterface::class));
        $container->set(TimeoutServiceInterface::class, $this->createMock(TimeoutServiceInterface::class));

        $this->assertInstanceOf(PlanInterface::class, $container->get(RunJobInterface::class));
    }

    public function testRunJobProxy(): void
    {
        $container = $this->buildContainer();

        $container->set(RunJobInterface::class, $this->createMock(RunJobInterface::class));
        $container->set(ErrorFactoryInterface::class, $this->createMock(ErrorFactoryInterface::class));

        $this->assertInstanceOf(RunJobInterface::class, $proxy = $container->get(RunJobInterface::class . ':proxy'));

        $this->assertInstanceOf(RunJobInterface::class, $proxy->train($this->createMock(ChefInterface::class)));

        $values = [];
        $this->assertInstanceOf(RunJobInterface::class, $proxy->prepare($values, $this->createMock(ChefInterface::class)));

        $this->assertInstanceOf(RunJobInterface::class, $proxy->validate($values));

        $this->assertInstanceOf(RunJobInterface::class, $proxy->fill($this->createMock(RecipeInterface::class)));

        $this->assertInstanceOf(RunJobInterface::class, $proxy->add($this->createMock(BowlInterface::class), 2));

        $this->assertInstanceOf(RunJobInterface::class, $proxy->addErrorHandler(fn (): true => true));
    }

    public function testYamlValidator(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.yaml_validation.xsd_url', 'foo');

        $this->assertInstanceOf(YamlValidator::class, $container->get(YamlValidator::class));
    }

    public function testQuotaFactory(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(QuotaFactory::class, $container->get(QuotaFactory::class));
    }

    public function testHookCompiler(): void
    {
        $container = $this->buildContainer();
        $container->set(HooksCollectionInterface::class, $this->createMock(HooksCollectionInterface::class));

        $this->assertInstanceOf(HookCompiler::class, $container->get(HookCompiler::class));
    }

    public function testImageCompiler(): void
    {
        $container = $this->buildContainer();

        $container->set('teknoo.east.paas.root_dir', '/foo');

        $container->set(
            'teknoo.east.paas.compilation.containers_images_library',
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

        $this->assertInstanceOf(ImageCompiler::class, $container->get(ImageCompiler::class));
    }

    public function testImageCompilerWithIterator(): void
    {
        $container = $this->buildContainer();

        $container->set('teknoo.east.paas.root_dir', '/foo');

        $container->set(
            'teknoo.east.paas.compilation.containers_images_library',
            new ArrayObject([
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
            ])
        );

        $this->assertInstanceOf(ImageCompiler::class, $container->get(ImageCompiler::class));
    }

    public function testImageCompilerWithoutLibrary(): void
    {
        $container = $this->buildContainer();

        $container->set('teknoo.east.paas.root_dir', '/foo');

        $this->assertInstanceOf(ImageCompiler::class, $container->get(ImageCompiler::class));
    }

    public function testImageCompilerWithBadLibrary(): void
    {
        $container = $this->buildContainer();

        $container->set('teknoo.east.paas.root_dir', '/foo');

        $container->set(
            'teknoo.east.paas.compilation.containers_images_library',
            new stdClass()
        );

        $this->expectException(InvalidArgumentException::class);
        $container->get(ImageCompiler::class);
    }

    public function testImageCompilerWithoutPathInDefinition(): void
    {
        $container = $this->buildContainer();

        $container->set('teknoo.east.paas.root_dir', '/foo');

        $container->set(
            'teknoo.east.paas.compilation.containers_images_library',
            [
                'php-fpm-74' => [
                    'build-name' => 'php-fpm',
                    'tag' => '7.4',
                ],
            ]
        );

        $this->expectException(\RuntimeException::class);

        $this->assertInstanceOf(ImageCompiler::class, $container->get(ImageCompiler::class));
    }

    public function testIngressCompiler(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(IngressCompiler::class, $container->get(IngressCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.ingresses_extends.library', ['foo' => []]);

        $this->assertInstanceOf(IngressCompiler::class, $container->get(IngressCompiler::class));
    }

    public function testIngressCompilerWithIterator(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(IngressCompiler::class, $container->get(IngressCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.ingresses_extends.library', new ArrayObject(['foo' => []]));

        $this->assertInstanceOf(IngressCompiler::class, $container->get(IngressCompiler::class));
    }

    public function testIngressCompilerWithoutLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(IngressCompiler::class, $container->get(IngressCompiler::class));

        $container = $this->buildContainer();

        $this->assertInstanceOf(IngressCompiler::class, $container->get(IngressCompiler::class));
    }

    public function testIngressCompilerWithBarLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(IngressCompiler::class, $container->get(IngressCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.ingresses_extends.library', new stdClass());

        $this->expectException(InvalidArgumentException::class);
        $this->assertInstanceOf(IngressCompiler::class, $container->get(IngressCompiler::class));
    }

    public function testPodCompiler(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.pods_extends.library', ['foo' => []]);
        $container->set('teknoo.east.paas.compilation.containers_extends.library', ['foo' => []]);

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));
    }

    public function testJobCompiler(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(JobCompiler::class, $container->get(JobCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.jods_extends.library', ['foo' => []]);

        $this->assertInstanceOf(JobCompiler::class, $container->get(JobCompiler::class));
    }

    public function testPodCompilerWithPodIteratorLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.pods_extends.library', new ArrayObject(['foo' => []]));
        $container->set('teknoo.east.paas.compilation.containers_extends.library', ['foo' => []]);

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));
    }

    public function testPodCompilerWithContainerIteratorLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.pods_extends.library', ['foo' => []]);
        $container->set('teknoo.east.paas.compilation.containers_extends.library', new ArrayObject(['foo' => []]));

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));
    }

    public function testPodCompilerWithoutPodLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.containers_extends.library', ['foo' => []]);

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));
    }

    public function testPodCompilerWithoutContainerLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.pods_extends.library', ['foo' => []]);

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));
    }

    public function testPodCompilerWithBadPodLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.pods_extends.library', new stdClass());
        $container->set('teknoo.east.paas.compilation.containers_extends.library', ['foo' => []]);


        $this->expectException(InvalidArgumentException::class);
        $container->get(PodCompiler::class);
    }

    public function testPodCompilerWithBadContainerLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(PodCompiler::class, $container->get(PodCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.pods_extends.library', ['foo' => []]);
        $container->set('teknoo.east.paas.compilation.containers_extends.library', new stdClass());

        $this->expectException(InvalidArgumentException::class);
        $container->get(PodCompiler::class);
    }

    public function testSecretCompiler(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(SecretCompiler::class, $container->get(SecretCompiler::class));
    }

    public function testFeaturesRequirementCompiler(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.features_requirement.list', []);

        $this->assertInstanceOf(FeaturesRequirementCompiler::class, $container->get(FeaturesRequirementCompiler::class));
    }

    public function testQuotaCompiler(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(QuotaCompiler::class, $container->get(QuotaCompiler::class));
    }

    public function testMapCompiler(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(MapCompiler::class, $container->get(MapCompiler::class));
    }

    public function testServiceCompiler(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(ServiceCompiler::class, $container->get(ServiceCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.services_extends.library', ['foo' => []]);

        $this->assertInstanceOf(ServiceCompiler::class, $container->get(ServiceCompiler::class));
    }

    public function testServiceCompilerWithIteratorLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(ServiceCompiler::class, $container->get(ServiceCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.services_extends.library', new ArrayObject(['foo' => []]));

        $this->assertInstanceOf(ServiceCompiler::class, $container->get(ServiceCompiler::class));
    }

    public function testServiceCompilerWithoutLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(ServiceCompiler::class, $container->get(ServiceCompiler::class));

        $container = $this->buildContainer();

        $this->assertInstanceOf(ServiceCompiler::class, $container->get(ServiceCompiler::class));
    }

    public function testServiceCompilerWithBadLib(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(ServiceCompiler::class, $container->get(ServiceCompiler::class));

        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.services_extends.library', new stdClass());

        $this->expectException(InvalidArgumentException::class);
        $container->get(ServiceCompiler::class);
    }

    public function testVolumeCompiler(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(VolumeCompiler::class, $container->get(VolumeCompiler::class));
    }

    public function testCompiledDeploymentFactory(): void
    {
        $container = $this->buildContainer();

        $this->assertInstanceOf(CompiledDeploymentFactoryInterface::class, $container->get(CompiledDeploymentFactoryInterface::class));

        $this->assertInstanceOf(CompiledDeploymentFactory::class, $container->get(CompiledDeploymentFactory::class));
    }

    public function testCompiledDeploymentFactoryWithInvalidXSD(): void
    {
        $container = $this->buildContainer();
        $container->set('teknoo.east.paas.compilation.yaml_validation.xsd_path', 'fooooooo/bar');

        $this->expectException(InvalidArgumentException::class);
        $this->assertInstanceOf(CompiledDeploymentFactory::class, $container->get(CompiledDeploymentFactory::class));
    }

    public function testCompilerCollectionInterface(): void
    {
        $container = $this->buildContainer();

        $container->set(HooksCollectionInterface::class, $this->createMock(HooksCollectionInterface::class));
        $container->set('teknoo.east.paas.root_dir', '/foo');

        $container->set(
            'teknoo.east.paas.compilation.containers_images_library',
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

        $this->assertInstanceOf(CompilerCollectionInterface::class, $collection = $container->get(CompilerCollectionInterface::class));

        foreach ($collection as $version => $compilers) {
            $this->assertIsString($version);

            $this->assertIsArray($compilers);
            foreach ($compilers as $pattern => $compiler) {
                $this->assertIsString($pattern);

                $this->assertInstanceOf(CompilerInterface::class, $compiler);
            }
        }
    }

    public function testConductor(): void
    {
        $container = $this->buildContainer();

        $container->set(CompiledDeploymentFactoryInterface::class, $this->createMock(CompiledDeploymentFactoryInterface::class));
        $container->set(PropertyAccessorInterface::class, $this->createMock(PropertyAccessorInterface::class));
        $container->set(YamlParserInterface::class, $this->createMock(YamlParserInterface::class));
        $container->set(HooksCollectionInterface::class, $this->createMock(HooksCollectionInterface::class));
        $container->set('teknoo.east.paas.root_dir', '/foo');

        $container->set(
            'teknoo.east.paas.compilation.containers_images_library',
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

        $this->assertInstanceOf(Conductor::class, $container->get(Conductor::class));

        $this->assertInstanceOf(Conductor::class, $container->get(ConductorInterface::class));
    }

    public function testConductorWithStorage(): void
    {
        $container = $this->buildContainer();

        $container->set(CompiledDeploymentFactoryInterface::class, $this->createMock(CompiledDeploymentFactoryInterface::class));
        $container->set(PropertyAccessorInterface::class, $this->createMock(PropertyAccessorInterface::class));
        $container->set(YamlParserInterface::class, $this->createMock(YamlParserInterface::class));
        $container->set(HooksCollectionInterface::class, $this->createMock(HooksCollectionInterface::class));
        $container->set('teknoo.east.paas.root_dir', '/foo');
        $container->set('teknoo.east.paas.default_storage_provider', 'foo');
        $container->set('teknoo.east.paas.default_storage_size', 'foo');
        $container->set('teknoo.east.paas.default_oci_registry_config_name', 'foo');

        $container->set(
            'teknoo.east.paas.compilation.containers_images_library',
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

        $this->assertInstanceOf(Conductor::class, $container->get(Conductor::class));

        $this->assertInstanceOf(Conductor::class, $container->get(ConductorInterface::class));
    }

    public function testPing(): void
    {
        $container = $this->buildContainer();
        $container->set(PingServiceInterface::class, $this->createMock(PingServiceInterface::class));

        $this->assertInstanceOf(Ping::class, $container->get(Ping::class));
    }

    public function testSetTimeLimit(): void
    {
        $container = $this->buildContainer();
        $container->set(TimeoutServiceInterface::class, $this->createMock(TimeoutServiceInterface::class));
        $container->set('teknoo.east.paas.worker.time_limit', 120);

        $this->assertInstanceOf(SetTimeLimit::class, $container->get(SetTimeLimit::class));
    }

    public function testUnsetTimeLimit(): void
    {
        $container = $this->buildContainer();
        $container->set(TimeoutServiceInterface::class, $this->createMock(TimeoutServiceInterface::class));

        $this->assertInstanceOf(UnsetTimeLimit::class, $container->get(UnsetTimeLimit::class));
    }
}
