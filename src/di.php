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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
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
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Conductor\Conductor;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\AddHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\NewJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface as DHI;
use Teknoo\East\Paas\Contracts\Recipe\Step\Misc\DispatchResultInterface as DRI;
use Teknoo\East\Paas\Recipe\Cookbook\AddHistory;
use Teknoo\East\Paas\Recipe\Cookbook\NewJob;
use Teknoo\East\Paas\Recipe\Cookbook\RunJob;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory as StepAddHistory;
use Teknoo\East\Paas\Recipe\Step\History\SendHistoryOverHTTP;
use Teknoo\East\Paas\Recipe\Step\Misc\PushResultOverHTTP;
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
use Teknoo\East\Paas\Contracts\DbSource\Repository\ClusterRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
use Teknoo\East\Paas\EndPoint\NewProjectEndPoint;
use Teknoo\East\Paas\Loader\AccountLoader;
use Teknoo\East\Paas\Loader\ClusterLoader;
use Teknoo\East\Paas\Loader\JobLoader;
use Teknoo\East\Paas\Loader\ProjectLoader;
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
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\History\SerializeHistory;
use Teknoo\East\Paas\Recipe\Step\Job\SerializeJob;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Writer\AccountWriter;
use Teknoo\East\Paas\Writer\ClusterWriter;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\East\Paas\Writer\ProjectWriter;
use Teknoo\Recipe\Recipe;
use Teknoo\Recipe\RecipeInterface;

use function DI\get;
use function DI\create;

return [
    //Loaders
    AccountLoader::class => create(AccountLoader::class)
        ->constructor(get(AccountRepositoryInterface::class)),
    JobLoader::class => create(JobLoader::class)
        ->constructor(get(JobRepositoryInterface::class)),
    ProjectLoader::class => create(ProjectLoader::class)
        ->constructor(get(ProjectRepositoryInterface::class)),
    ClusterLoader::class => create(ClusterLoader::class)
        ->constructor(get(ClusterRepositoryInterface::class)),

    //Writer
    AccountWriter::class => create(AccountWriter::class)
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
    StepAddHistory::class => create(),
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

    DHI::class . ':resolver' => static function (ContainerInterface $container): DHI {
        if ($container->has(DHI::class)) {
            return $container->get(DHI::class);
        }

        return $container->get(SendHistoryOverHTTP::class);
    },

    SendHistoryOverHTTP::class => create()
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

    DRI::class . ':resolver' => static function (ContainerInterface $container): DRI {
        if ($container->has(DRI::class)) {
            return $container->get(DRI::class);
        }

        return $container->get(PushResultOverHTTP::class);
    },

    PushResultOverHTTP::class => create()
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
            get(DHI::class . ':resolver'),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    BuildVolumes::class => create()
        ->constructor(
            get(DHI::class . ':resolver'),
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
            get(DHI::class . ':resolver'),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    Exposing::class => create()
        ->constructor(
            get(DHI::class . ':resolver'),
            get(ResponseFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),
    HookBuildContainer::class => create()
        ->constructor(
            get(DHI::class . ':resolver'),
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

    //Base recipe
    RecipeInterface::class => get(Recipe::class),
    Recipe::class => create(),

    //Cookbooks
    NewJobInterface::class => get(NewJob::class),
    NewJob::class => create()
        ->constructor(
            get(RecipeInterface::class),
            get(GetProject::class),
            get(GetEnvironment::class),
            get(GetVariables::class),
            get(CreateNewJob::class),
            get(PrepareJob::class),
            get(SaveJob::class),
            get(SerializeJob::class),
            get(DispatchJobInterface::class),
            get(DisplayJob::class),
            get(DisplayError::class),
        ),

    AddHistoryInterface::class => get(AddHistory::class),
    AddHistory::class => create()
        ->constructor(
            get(RecipeInterface::class),
            get(ReceiveHistory::class),
            get(DeserializeHistory::class),
            get(GetProject::class),
            get(GetJob::class),
            get(StepAddHistory::class),
            get(SaveJob::class),
            get(SerializeHistory::class),
            get(DisplayHistory::class),
            get(DisplayError::class),
        ),

    RunJobInterface::class => get(RunJob::class),
    RunJob::class => create()
        ->constructor(
            get(RecipeInterface::class),
            get(DHI::class . ':resolver'),
            get(ReceiveJob::class),
            get(DeserializeJob::class),
            get(PrepareWorkspace::class),
            get(ConfigureCloningAgent::class),
            get(CloneRepository::class),
            get(ConfigureConductor::class),
            get(ReadDeploymentConfiguration::class),
            get(CompileDeployment::class),
            get(HookBuildContainer::class),
            get(ConfigureImagesBuilder::class),
            get(BuildImages::class),
            get(BuildVolumes::class),
            get(ConfigureClusterClient::class),
            get(Deploying::class),
            get(Exposing::class),
            get(DRI::class . ':resolver'),
            get(DisplayHistory::class),
            get(DisplayError::class),
        ),
];
