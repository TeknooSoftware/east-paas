<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\Compiler\HookCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ImageCompiler;
use Teknoo\East\Paas\Compilation\Compiler\IngressCompiler;
use Teknoo\East\Paas\Compilation\Compiler\PodCompiler;
use Teknoo\East\Paas\Compilation\Compiler\SecretCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ServiceCompiler;
use Teknoo\East\Paas\Compilation\Compiler\VolumeCompiler;
use Teknoo\East\Paas\Compilation\CompiledDeployment;
use Teknoo\East\Paas\Compilation\CompiledDeploymentFactory;
use Teknoo\East\Paas\Compilation\Conductor;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerCollectionInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\AddHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\EditAccountEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\EditProjectEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\NewAccountEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\NewJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\NewProjectEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\AddHistoryStepsInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\EditAccountEndPointStepsInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\EditProjectEndPointStepsInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\NewAccountEndPointStepsInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\NewJobErrorsHandlersInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\NewJobStepsInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\NewProjectEndPointStepsInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\RunJobStepsInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface as DHI;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface as DRI;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Parser\YamlValidator;
use Teknoo\East\Paas\Recipe\AbstractAdditionalStepsList;
use Teknoo\East\Paas\Recipe\Cookbook\AbstractEditObjectEndPoint;
use Teknoo\East\Paas\Recipe\Cookbook\AddHistory;
use Teknoo\East\Paas\Recipe\Cookbook\NewAccountEndPoint;
use Teknoo\East\Paas\Recipe\Cookbook\NewJob;
use Teknoo\East\Paas\Recipe\Cookbook\NewProjectEndPoint;
use Teknoo\East\Paas\Recipe\Cookbook\RunJob;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory as StepAddHistory;
use Teknoo\East\Paas\Recipe\Step\Misc\DispatchError;
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
use Teknoo\East\Common\Service\DatesService;
use Teknoo\East\Common\Service\DeletingService;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\AccountRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ClusterRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\JobRepositoryInterface;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;
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
use Teknoo\East\Paas\Recipe\Step\Misc\GetVariables;
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
use Teknoo\Recipe\BaseRecipeInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\CookbookInterface;
use Teknoo\Recipe\Recipe;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;
use Traversable;

use function DI\get;
use function DI\create;
use function DI\value;
use function file_get_contents;
use function is_dir;

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

    //YamlValidator
    YamlValidator::class => create(YamlValidator::class)
        ->constructor('root'),

    //Compiler
    HookCompiler::class => create()
        ->constructor(get(HooksCollectionInterface::class)),
    ImageCompiler::class => static function (ContainerInterface $container): ImageCompiler {
        $imagesLibrary = $container->get('teknoo.east.paas.conductor.images_library');
        $rootPath = $container->get('teknoo.east.paas.root_dir');
        foreach ($imagesLibrary as &$image) {
            if (empty($image['path']) || is_dir($image['path'])) {
                throw new RuntimeException('Missing path');
            }

            $image['path'] = $rootPath . $image['path'];
        }

        return new ImageCompiler($imagesLibrary);
    },
    IngressCompiler::class => create(),
    PodCompiler::class => create(),
    SecretCompiler::class => create(),
    ServiceCompiler::class => create(),
    VolumeCompiler::class => create(),

    CompilerCollectionInterface::class => static function (ContainerInterface $container): CompilerCollectionInterface {
        $collection = new class implements CompilerCollectionInterface {
            /**
             * @var array<string, CompilerInterface>
             */
            private array $collection = [];

            public function add(string $pattern, CompilerInterface $compiler): void
            {
                $this->collection[$pattern] = $compiler;
            }

            public function getIterator(): Traversable
            {
                yield from $this->collection;
            }
        };

        $collection->add('[secrets]', $container->get(SecretCompiler::class));
        $collection->add('[volumes]', $container->get(VolumeCompiler::class));
        $collection->add('[images]', $container->get(ImageCompiler::class));
        $collection->add('[builds]', $container->get(HookCompiler::class));
        $collection->add('[pods]', $container->get(PodCompiler::class));
        $collection->add('[services]', $container->get(ServiceCompiler::class));
        $collection->add('[ingresses]', $container->get(IngressCompiler::class));

        return $collection;
    },

    //Conductor
    CompiledDeploymentFactoryInterface::class => get(CompiledDeploymentFactory::class),
    CompiledDeploymentFactory::class => create()
        ->constructor(
            CompiledDeployment::class,
            (string) file_get_contents(__DIR__ . '/Contracts/Configuration/paas_validation.xsd'),
        ),

    ConductorInterface::class => get(Conductor::class),
    Conductor::class => static function (ContainerInterface $container): Conductor {
        $storageProvider = null;
        if ($container->has('teknoo.east.paas.default_storage_provider')) {
            $storageProvider = $container->get('teknoo.east.paas.default_storage_provider');
        }

        return new Conductor(
            $container->get(CompiledDeploymentFactoryInterface::class),
            $container->get(PropertyAccessorInterface::class),
            $container->get(YamlParserInterface::class),
            $container->get(YamlValidator::class),
            $container->get(CompilerCollectionInterface::class),
            $storageProvider
        );
    },

    Directory::class => create(),

    //Recipes steps
    //History
    StepAddHistory::class => create(),
    DeserializeHistory::class => create()
        ->constructor(
            get(DeserializerInterface::class),
        ),
    ReceiveHistory::class => create(),

    //Job
    CreateNewJob::class => create(),
    DeserializeJob::class => static function (ContainerInterface $container): DeserializeJob {
        return new DeserializeJob(
            $container->get(DeserializerInterface::class),
            $_ENV + $container->get('teknoo.east.paas.worker.global_variables'),
        );
    },
    GetJob::class => create()
        ->constructor(
            get(JobLoader::class),
        ),
    PrepareJob::class => static function (ContainerInterface $container): PrepareJob {
        $prefereRealDate = true;
        if (true === $container->has('teknoo.east.paas.symfony.prepare-job.prefer-real-date')) {
            $prefereRealDate = (bool) $container->get('teknoo.east.paas.symfony.prepare-job.prefer-real-date');
        }

        return new PrepareJob(
            dateTimeService: $container->get(DatesService::class),
            errorFactory: $container->get(ErrorFactoryInterface::class),
            preferRealDate: $prefereRealDate,
        );
    },

    ReceiveJob::class => create(),
    SaveJob::class => create()
        ->constructor(get(JobWriter::class)),
    SerializeJob::class => static function (ContainerInterface $container): SerializeJob {
        return new SerializeJob(
            $container->get(SerializerInterface::class),
        );
    },

    //Job
    GetVariables::class => create(),
    DispatchError::class => create()
        ->constructor(
            get(ErrorFactoryInterface::class),
        ),

    //Project
    GetEnvironment::class => create(),
    GetProject::class => create()
        ->constructor(
            get(ProjectLoader::class),
        ),

    //Worker
    BuildImages::class => create()
        ->constructor(
            get(DHI::class),
        ),
    BuildVolumes::class => create()
        ->constructor(
            get(DHI::class),
        ),
    CloneRepository::class => create(),
    CompileDeployment::class => create(),
    ConfigureCloningAgent::class => create()
        ->constructor(
            get(CloningAgentInterface::class),
        ),
    ConfigureConductor::class => create()
        ->constructor(get(ConductorInterface::class)),
    ConfigureImagesBuilder::class => create()
        ->constructor(
            get(BuilderInterface::class),
        ),
    ConfigureClusterClient::class => create()
        ->constructor(
            get(Directory::class),
        ),
    Deploying::class => create()
        ->constructor(
            get(DHI::class),
        ),
    Exposing::class => create()
        ->constructor(
            get(DHI::class),
        ),
    HookingDeployment::class => create()
        ->constructor(
            get(DHI::class),
        ),
    PrepareWorkspace::class => create()
        ->constructor(get(JobWorkspaceInterface::class)),
    ReadDeploymentConfiguration::class => create(),

    //Base recipe
    OriginalRecipeInterface::class => get(Recipe::class),
    Recipe::class => create(),

    //Cookbooks
    NewAccountEndPointStepsInterface::class => static function (): NewAccountEndPointStepsInterface {
        return new class extends AbstractAdditionalStepsList implements NewAccountEndPointStepsInterface {
        };
    },
    NewProjectEndPointStepsInterface::class => static function (): NewProjectEndPointStepsInterface {
        return new class extends AbstractAdditionalStepsList implements NewProjectEndPointStepsInterface {
        };
    },
    EditAccountEndPointStepsInterface::class => static function (): EditAccountEndPointStepsInterface {
        return new class extends AbstractAdditionalStepsList implements EditAccountEndPointStepsInterface {
        };
    },
    EditProjectEndPointStepsInterface::class => static function (): EditProjectEndPointStepsInterface {
        return new class extends AbstractAdditionalStepsList implements EditProjectEndPointStepsInterface {
        };
    },
    AddHistoryStepsInterface::class => static function (): AddHistoryStepsInterface {
        return new class extends AbstractAdditionalStepsList implements AddHistoryStepsInterface {
        };
    },
    NewJobStepsInterface::class => static function (): NewJobStepsInterface {
        return new class extends AbstractAdditionalStepsList implements NewJobStepsInterface {
        };
    },
    NewJobErrorsHandlersInterface::class => static function (): NewJobErrorsHandlersInterface {
        return new class extends AbstractAdditionalStepsList implements NewJobErrorsHandlersInterface {
        };
    },
    RunJobStepsInterface::class => static function (): RunJobStepsInterface {
        return new class extends AbstractAdditionalStepsList implements RunJobStepsInterface {
        };
    },

    NewAccountEndPointInterface::class => get(NewAccountEndPoint::class),
    NewAccountEndPoint::class => static function (
        ContainerInterface $container
    ): NewAccountEndPoint {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorMapping = null;
        if ($container->has('teknoo.east.common.cookbook.default_error_template')) {
            $defaultErrorMapping = $container->get('teknoo.east.common.cookbook.default_error_template');
        }

        return new NewAccountEndPoint(
            $container->get(OriginalRecipeInterface::class),
            $container->get(CreateObject::class),
            $container->get(FormHandlingInterface::class),
            $container->get(FormProcessingInterface::class),
            $container->get(SaveObject::class),
            $container->get(RedirectClientInterface::class),
            $container->get(RenderFormInterface::class),
            $container->get(RenderError::class),
            $container->get(NewAccountEndPointStepsInterface::class),
            $accessControl,
            $defaultErrorMapping,
        );
    },

    EditAccountEndPointInterface::class => static function (
        ContainerInterface $container
    ): EditAccountEndPointInterface {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorMapping = null;
        if ($container->has('teknoo.east.common.cookbook.default_error_template')) {
            $defaultErrorMapping = $container->get('teknoo.east.common.cookbook.default_error_template');
        }

        return new class (
            $container->get(OriginalRecipeInterface::class),
            $container->get(LoadObject::class),
            $container->get(FormHandlingInterface::class),
            $container->get(FormProcessingInterface::class),
            $container->get(SaveObject::class),
            $container->get(RenderFormInterface::class),
            $container->get(RenderError::class),
            $accessControl,
            $container->get(EditAccountEndPointStepsInterface::class)
        ) extends AbstractEditObjectEndPoint implements EditAccountEndPointInterface {
        };
    },

    EditProjectEndPointInterface::class => static function (
        ContainerInterface $container
    ): EditProjectEndPointInterface {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorMapping = null;
        if ($container->has('teknoo.east.common.cookbook.default_error_template')) {
            $defaultErrorMapping = $container->get('teknoo.east.common.cookbook.default_error_template');
        }

        return new class (
            $container->get(OriginalRecipeInterface::class),
            $container->get(LoadObject::class),
            $container->get(FormHandlingInterface::class),
            $container->get(FormProcessingInterface::class),
            $container->get(SaveObject::class),
            $container->get(RenderFormInterface::class),
            $container->get(RenderError::class),
            $accessControl,
            $container->get(EditProjectEndPointStepsInterface::class),
            $defaultErrorMapping
        ) extends AbstractEditObjectEndPoint implements EditProjectEndPointInterface {
        };
    },

    NewProjectEndPointInterface::class => get(NewProjectEndPoint::class),
    NewProjectEndPoint::class => static function (ContainerInterface $container): NewProjectEndPoint {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorMapping = null;
        if ($container->has('teknoo.east.common.cookbook.default_error_template')) {
            $defaultErrorMapping = $container->get('teknoo.east.common.cookbook.default_error_template');
        }

        return new NewProjectEndPoint(
            $container->get(OriginalRecipeInterface::class),
            $container->get(LoadObject::class),
            $accessControl,
            $container->get(CreateObject::class),
            $container->get(FormHandlingInterface::class),
            $container->get(FormProcessingInterface::class),
            $container->get(SaveObject::class),
            $container->get(RedirectClientInterface::class),
            $container->get(RenderFormInterface::class),
            $container->get(RenderError::class),
            $container->get(NewProjectEndPointStepsInterface::class),
            $defaultErrorMapping,
        );
    },

    NewJobInterface::class => get(NewJob::class),
    NewJob::class => create()
        ->constructor(
            get(OriginalRecipeInterface::class),
            get(GetProject::class),
            get(GetEnvironment::class),
            get(GetVariables::class),
            get(CreateNewJob::class),
            get(PrepareJob::class),
            get(SaveJob::class),
            get(SerializeJob::class),
            get(NewJobStepsInterface::class),
            get(DispatchJobInterface::class),
            get(SendJobInterface::class),
            get(DispatchError::class),
            get(NewJobErrorsHandlersInterface::class),
        ),

    AddHistoryInterface::class => get(AddHistory::class),
    AddHistory::class => create()
        ->constructor(
            get(OriginalRecipeInterface::class),
            get(ReceiveHistory::class),
            get(DeserializeHistory::class),
            get(GetProject::class),
            get(GetJob::class),
            get(StepAddHistory::class),
            get(SaveJob::class),
            get(AddHistoryStepsInterface::class),
            get(SendHistoryInterface::class),
            get(DispatchError::class),
        ),

    RunJobInterface::class => get(RunJob::class),
    RunJob::class => create()
        ->constructor(
            get(OriginalRecipeInterface::class),
            get(DHI::class),
            get(ReceiveJob::class),
            get(DeserializeJob::class),
            get(PrepareWorkspace::class),
            get(ConfigureCloningAgent::class),
            get(CloneRepository::class),
            get(ConfigureConductor::class),
            get(ReadDeploymentConfiguration::class),
            get(CompileDeployment::class),
            get(HookingDeployment::class),
            get(ConfigureImagesBuilder::class),
            get(BuildImages::class),
            get(BuildVolumes::class),
            get(ConfigureClusterClient::class),
            get(Deploying::class),
            get(Exposing::class),
            get(RunJobStepsInterface::class),
            get(DRI::class),
            get(SendHistoryInterface::class),
        ),

    RunJobInterface::class . ':proxy' => static function (ContainerInterface $container): RunJobInterface {
        return new class ($container) implements RunJobInterface {
            private ?RunJobInterface $runJob = null;

            public function __construct(
                private ContainerInterface $container,
            ) {
            }

            private function getRunJob(): RunJobInterface
            {
                return $this->runJob ?? ($this->runJob = $this->container->get(RunJobInterface::class));
            }

            public function train(ChefInterface $chef): BaseRecipeInterface
            {
                $this->getRunJob()->train($chef);

                return $this;
            }

            public function prepare(array &$workPlan, ChefInterface $chef): BaseRecipeInterface
            {
                $this->getRunJob()->prepare($workPlan, $chef);

                return $this;
            }

            public function validate(mixed $value): BaseRecipeInterface
            {
                $this->getRunJob()->validate($value);

                return $this;
            }

            public function fill(OriginalRecipeInterface $recipe): CookbookInterface
            {
                $this->getRunJob()->fill($recipe);

                return $this;
            }
        };
    },
];
