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

namespace Teknoo\East\Paas;

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Conductor\Conductor;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Website\DBSource\ManagerInterface;
use Teknoo\East\Website\Service\DatesService;
use Teknoo\East\Website\Service\DeletingService;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
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
use Teknoo\East\Paas\EndPoint\NewProjectEndPoint;
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
use Teknoo\East\Paas\Recipe\Step\Misc\GetVariables;
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
use Teknoo\Recipe\Bowl\Bowl;
use Teknoo\Recipe\Bowl\BowlInterface;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\Recipe;

use function DI\get;
use function DI\create;

return [
    //Loaders
    AccountLoader::class => create(AccountLoader::class)
        ->constructor(get(AccountRepositoryInterface::class)),
    BillingInformationLoader::class => create(BillingInformationLoader::class)
        ->constructor(get(BillingInformationRepositoryInterface::class)),
    PaymentInformationLoader::class => create(PaymentInformationLoader::class)
        ->constructor(get(PaymentInformationRepositoryInterface::class)),
    JobLoader::class => create(JobLoader::class)
        ->constructor(get(JobRepositoryInterface::class)),
    ProjectLoader::class => create(ProjectLoader::class)
        ->constructor(get(ProjectRepositoryInterface::class)),
    ClusterLoader::class => create(ClusterLoader::class)
        ->constructor(get(ClusterRepositoryInterface::class)),

    //Writer
    AccountWriter::class => create(AccountWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    BillingInformationWriter::class => create(BillingInformationWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    PaymentInformationWriter::class => create(PaymentInformationWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    ProjectWriter::class => create(ProjectWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    JobWriter::class => create(JobWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),
    ClusterWriter::class => create(ClusterWriter::class)
        ->constructor(get(ManagerInterface::class), get(DatesService::class)),

    //Deleting
    'teknoo.east.paas.deleting.account' => create(DeletingService::class)
        ->constructor(get(AccountWriter::class), get(DatesService::class)),
    'teknoo.east.paas.deleting.billing_information' => create(DeletingService::class)
        ->constructor(get(BillingInformationWriter::class), get(DatesService::class)),
    'teknoo.east.paas.deleting.payment_information' => create(DeletingService::class)
        ->constructor(get(PaymentInformationWriter::class), get(DatesService::class)),
    'teknoo.east.paas.deleting.project' => create(DeletingService::class)
        ->constructor(get(ProjectWriter::class), get(DatesService::class)),
    'teknoo.east.paas.deleting.job' => create(DeletingService::class)
        ->constructor(get(JobWriter::class), get(DatesService::class)),
    'teknoo.east.paas.deleting.cluster' => create(DeletingService::class)
        ->constructor(get(ClusterWriter::class), get(DatesService::class)),

    //Conductor
    ConductorInterface::class => get(Conductor::class),
    Conductor::class => static function (ContainerInterface $container): Conductor {
        $imagesLibrary = $container->get('teknoo.east.paas.conductor.images_library');
        $rootPath = $container->get('teknoo.east.paas.root_dir');
        foreach ($imagesLibrary as &$image) {
            if (empty($image['path']) || \is_dir($image['path'])) {
                throw new \RuntimeException('Missing path');
            }

            $image['path'] = $rootPath . $image['path'];
        }

        return new Conductor(
            $container->get(PropertyAccessorInterface::class),
            $container->get(YamlParserInterface::class),
            $imagesLibrary,
            $container->get(HooksCollectionInterface::class)
        );
    },

    NewProjectEndPoint::class => create(),

    //Recipes steps
    //History
    AddHistory::class => create(),
    DisplayHistory::class => create()
        ->constructor(
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    DeserializeHistory::class => create()
        ->constructor(
            get(DeserializerInterface::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    ReceiveHistory::class => create(),
    SendHistory::class => create()
        ->constructor(
            get(DatesService::class),
            get('teknoo.east.paas.worker.add_history_pattern'),
            get(UriFactoryInterface::class),
            get(RequestFactoryInterface::class),
            get(StreamFactoryInterface::class),
            get(ClientInterface::class)
        ),
    SerializeHistory::class => static function (ContainerInterface $container): SerializeHistory {
        return new SerializeHistory(
            $container->get(SerializerInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class)
        );
    },

    //Job
    CreateNewJob::class => create(),
    DeserializeJob::class => create()
        ->constructor(
            get(DeserializerInterface::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class),
            get('teknoo.east.paas.worker.global_variables')
        ),
    DisplayJob::class => create()
        ->constructor(
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    GetJob::class => create()
        ->constructor(
            get(JobLoader::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    PrepareJob::class => create()
        ->constructor(
            get(DatesService::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    ReceiveJob::class => create(),
    SaveJob::class => create()
        ->constructor(get(JobWriter::class)),
    SerializeJob::class => static function (ContainerInterface $container): SerializeJob {
        return new SerializeJob(
            $container->get(SerializerInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
        );
    },

    //Misc
    DisplayError::class => static function (ContainerInterface $container): DisplayError {
        return new DisplayError(
            $container->get(SerializerInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class)
        );
    },
    PushResult::class => create()
        ->constructor(
            get(DatesService::class),
            get('teknoo.east.paas.worker.add_history_pattern'),
            get(NormalizerInterface::class),
            get(UriFactoryInterface::class),
            get(RequestFactoryInterface::class),
            get(StreamFactoryInterface::class),
            get(ClientInterface::class),
            get(ResponseFactoryInterface::class)
        ),
    GetVariables::class => create(),

    //Project
    GetEnvironment::class => create(),
    GetProject::class => create()
        ->constructor(
            get(ProjectLoader::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),


    //Worker
    BuildImages::class => create()
        ->constructor(
            get(SendHistory::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    BuildVolumes::class => create()
        ->constructor(
            get(SendHistory::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    CloneRepository::class => create(),
    CompileDeployment::class => create()
        ->constructor(
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    ConfigureCloningAgent::class => create()
        ->constructor(
            get(CloningAgentInterface::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    ConfigureConductor::class => create()
        ->constructor(get(ConductorInterface::class)),
    ConfigureImagesBuilder::class => create()
        ->constructor(
            get(BuilderInterface::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    ConfigureClusterClient::class => create()
        ->constructor(
            get(ClusterClientInterface::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    Deploying::class => create()
        ->constructor(
            get(SendHistory::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    Exposing::class => create()
        ->constructor(
            get(SendHistory::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    HookBuildContainer::class => create()
        ->constructor(
            get(SendHistory::class),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    PrepareWorkspace::class => create()
        ->constructor(get(JobWorkspaceInterface::class)),
    ReadDeploymentConfiguration::class => create()
        ->constructor(
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),

    //Recipes
    NewJobRecipe::class => static function (ContainerInterface $container) {
        $recipe = new class extends Recipe implements NewJobRecipe {
            public static function statesListDeclaration(): array
            {
                return [];
            }
        };

        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));

        $recipe = $recipe->cook($container->get(GetProject::class), GetProject::class, [], 0);
        $recipe = $recipe->cook($container->get(GetEnvironment::class), GetEnvironment::class, [], 1);
        $recipe = $recipe->cook($container->get(GetVariables::class), GetVariables::class, [], 1);
        $recipe = $recipe->cook($container->get(CreateNewJob::class), CreateNewJob::class, [], 2);
        $recipe = $recipe->cook($container->get(PrepareJob::class), PrepareJob::class, [], 3);
        $recipe = $recipe->cook($container->get(SaveJob::class), SaveJob::class, [], 4);
        $recipe = $recipe->cook($container->get(SerializeJob::class), SerializeJob::class, [], 5);
        $recipe = $recipe->cook(
            $container->get(DispatchJobInterface::class),
            DispatchJobInterface::class,
            [],
            6
        );
        $recipe = $recipe->cook($container->get(DisplayJob::class), DisplayJob::class, [], 7);

        $recipe = $recipe->onError(new Bowl($container->get(DisplayError::class), []));

        return $recipe;
    },

    AddHistoryRecipe::class => static function (ContainerInterface $container) {
        $recipe = new class extends Recipe implements AddHistoryRecipe {
            public static function statesListDeclaration(): array
            {
                return [];
            }
        };

        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));

        $recipe = $recipe->cook($container->get(ReceiveHistory::class), ReceiveHistory::class, [], 0);
        $recipe = $recipe->cook($container->get(DeserializeHistory::class), DeserializeHistory::class, [], 1);
        $recipe = $recipe->cook($container->get(GetProject::class), GetProject::class, [], 2);
        $recipe = $recipe->cook($container->get(GetJob::class), GetJob::class, [], 3);
        $recipe = $recipe->cook($container->get(AddHistory::class), AddHistory::class, [], 4);
        $recipe = $recipe->cook($container->get(SaveJob::class), SaveJob::class, [], 5);
        $recipe = $recipe->cook($container->get(SerializeHistory::class), SerializeHistory::class, [], 6);
        $recipe = $recipe->cook($container->get(DisplayHistory::class), DisplayHistory::class, [], 7);

        $recipe = $recipe->onError(new Bowl($container->get(DisplayError::class), []));

        return $recipe;
    },

    RunJobRecipe::class => static function (ContainerInterface $container) {
        $recipe = new class extends Recipe implements RunJobRecipe {
            public static function statesListDeclaration(): array
            {
                return [];
            }
        };

        $recipe = $recipe->require(new Ingredient(ServerRequestInterface::class, 'request'));

        $notification = $container->get(SendHistory::class);
        $notificationMapping = ['step' => BowlInterface::METHOD_NAME];

        //Startup Run
        $recipe = $recipe->cook(
            $container->get(ReceiveJob::class),
            ReceiveJob::class,
            [],
            RunJobRecipe::STEP_RECEIVE_JOB
        );

        $recipe = $recipe->cook(
            $container->get(DeserializeJob::class),
            DeserializeJob::class,
            [],
            RunJobRecipe::STEP_DESERIALIZE_JOB
        );

        //Prepare workspace
        $recipe = $recipe->cook(
            $notification,
            PrepareWorkspace::class,
            $notificationMapping,
            RunJobRecipe::STEP_PREPARE_WORKSPACE
        );
        $recipe = $recipe->cook(
            $container->get(PrepareWorkspace::class),
            PrepareWorkspace::class,
            [],
            RunJobRecipe::STEP_PREPARE_WORKSPACE
        );

        $recipe = $recipe->cook(
            $notification,
            ConfigureCloningAgent::class,
            $notificationMapping,
            RunJobRecipe::STEP_CONFIGURE_CLONING_AGENT
        );
        $recipe = $recipe->cook(
            $container->get(ConfigureCloningAgent::class),
            ConfigureCloningAgent::class,
            [],
            RunJobRecipe::STEP_CONFIGURE_CLONING_AGENT
        );

        $recipe = $recipe->cook(
            $notification,
            CloneRepository::class,
            $notificationMapping,
            RunJobRecipe::STEP_CLONE_REPOSITORY
        );
        $recipe = $recipe->cook(
            $container->get(CloneRepository::class),
            CloneRepository::class,
            [],
            RunJobRecipe::STEP_CLONE_REPOSITORY
        );

        //Configure Deployment
        $recipe = $recipe->cook(
            $notification,
            ConfigureConductor::class,
            $notificationMapping,
            RunJobRecipe::STEP_CONFIGURE_CONDUCTOR
        );
        $recipe = $recipe->cook(
            $container->get(ConfigureConductor::class),
            ConfigureConductor::class,
            [],
            RunJobRecipe::STEP_CONFIGURE_CONDUCTOR
        );

        $recipe = $recipe->cook(
            $notification,
            ReadDeploymentConfiguration::class,
            $notificationMapping,
            RunJobRecipe::STEP_READ_DEPLOYMENT_CONFIGURATION
        );
        $recipe = $recipe->cook(
            $container->get(ReadDeploymentConfiguration::class),
            ReadDeploymentConfiguration::class,
            [],
            RunJobRecipe::STEP_READ_DEPLOYMENT_CONFIGURATION
        );

        $recipe = $recipe->cook(
            $notification,
            CompileDeployment::class,
            $notificationMapping,
            RunJobRecipe::STEP_COMPILE_DEPLOYMENT
        );
        $recipe = $recipe->cook(
            $container->get(CompileDeployment::class),
            CompileDeployment::class,
            [],
            RunJobRecipe::STEP_COMPILE_DEPLOYMENT
        );

        //Configure Build Image
        $recipe = $recipe->cook(
            $notification,
            HookBuildContainer::class,
            $notificationMapping,
            RunJobRecipe::STEP_HOOK_PRE_BUILD_CONTAINER
        );
        $recipe = $recipe->cook(
            $container->get(HookBuildContainer::class),
            HookBuildContainer::class,
            [],
            RunJobRecipe::STEP_HOOK_PRE_BUILD_CONTAINER
        );

        $recipe = $recipe->cook(
            $notification,
            ConfigureImagesBuilder::class,
            $notificationMapping,
            RunJobRecipe::STEP_CONNECT_CONTAINER_REPOSITORY
        );
        $recipe = $recipe->cook(
            $container->get(ConfigureImagesBuilder::class),
            ConfigureImagesBuilder::class,
            [],
            RunJobRecipe::STEP_CONNECT_CONTAINER_REPOSITORY
        );

        $recipe = $recipe->cook(
            $notification,
            BuildImages::class,
            $notificationMapping,
            RunJobRecipe::STEP_BUILD_IMAGE
        );
        $recipe = $recipe->cook(
            $container->get(BuildImages::class),
            BuildImages::class,
            [],
            RunJobRecipe::STEP_BUILD_IMAGE
        );

        $recipe = $recipe->cook(
            $notification,
            BuildVolumes::class,
            $notificationMapping,
            RunJobRecipe::STEP_BUILD_VOLUME
        );
        $recipe = $recipe->cook(
            $container->get(BuildVolumes::class),
            BuildVolumes::class,
            [],
            RunJobRecipe::STEP_BUILD_VOLUME
        );

        //Do Deployment
        $recipe = $recipe->cook(
            $notification,
            ConfigureClusterClient::class,
            $notificationMapping,
            RunJobRecipe::STEP_CONNECT_MASTER
        );
        $recipe = $recipe->cook(
            $container->get(ConfigureClusterClient::class),
            ConfigureClusterClient::class,
            [],
            RunJobRecipe::STEP_CONNECT_MASTER
        );

        $recipe = $recipe->cook(
            $notification,
            Deploying::class,
            $notificationMapping,
            RunJobRecipe::STEP_DEPLOYING
        );
        $recipe = $recipe->cook(
            $container->get(Deploying::class),
            Deploying::class,
            [],
            RunJobRecipe::STEP_DEPLOYING
        );

        $recipe = $recipe->cook(
            $notification,
            Exposing::class,
            $notificationMapping,
            RunJobRecipe::STEP_EXPOSING
        );
        $recipe = $recipe->cook(
            $container->get(Exposing::class),
            Exposing::class,
            [],
            RunJobRecipe::STEP_EXPOSING
        );

        //Final
        $recipe = $recipe->cook(
            $container->get(PushResult::class),
            PushResult::class,
            [],
            RunJobRecipe::STEP_FINAL
        );

        $recipe = $recipe->cook(
            $container->get(DisplayHistory::class),
            DisplayHistory::class,
            [],
            RunJobRecipe::STEP_FINAL
        );

        $recipe = $recipe->onError(new Bowl($container->get(PushResult::class), ['result' => 'exception']));
        $recipe = $recipe->onError(new Bowl($container->get(DisplayError::class), []));

        return $recipe;
    },
];
