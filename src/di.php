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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas;

use Psr\Container\ContainerInterface;
use Teknoo\East\Foundation\Liveness\PingServiceInterface;
use Teknoo\East\Foundation\Liveness\TimeoutServiceInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\Compiler\DefaultsCompiler;
use Teknoo\East\Paas\Compilation\Compiler\Exception\MissingAttributeException;
use Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirementCompiler;
use Teknoo\East\Paas\Compilation\Compiler\MapCompiler;
use Teknoo\East\Paas\Compilation\Compiler\HookCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ImageCompiler;
use Teknoo\East\Paas\Compilation\Compiler\IngressCompiler;
use Teknoo\East\Paas\Compilation\Compiler\PodCompiler;
use Teknoo\East\Paas\Compilation\Compiler\Quota\ComputeAvailability;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as QuotaFactory;
use Teknoo\East\Paas\Compilation\Compiler\Quota\MemoryAvailability;
use Teknoo\East\Paas\Compilation\Compiler\QuotaCompiler;
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
use Teknoo\East\Paas\Contracts\Recipe\Plan\AddHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\EditAccountEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\EditProjectEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\NewAccountEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\NewJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\NewProjectEndPointInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\RunJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface as DHI;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\DispatchResultInterface as DRI;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\DI\Exception\InvalidArgumentException;
use Teknoo\East\Paas\Job\History\SerialGenerator;
use Teknoo\East\Paas\Parser\YamlValidator;
use Teknoo\East\Paas\Recipe\Plan\AbstractEditObjectEndPoint;
use Teknoo\East\Paas\Recipe\Plan\AddHistory;
use Teknoo\East\Paas\Recipe\Plan\NewAccountEndPoint;
use Teknoo\East\Paas\Recipe\Plan\NewJob;
use Teknoo\East\Paas\Recipe\Plan\NewProjectEndPoint;
use Teknoo\East\Paas\Recipe\Plan\RunJob;
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
use Teknoo\Recipe\Bowl\BowlInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\EditablePlanInterface;
use Teknoo\Recipe\Plan\Step;
use Teknoo\Recipe\PlanInterface;
use Teknoo\Recipe\Recipe;
use Teknoo\Recipe\RecipeInterface as OriginalRecipeInterface;
use Traversable;

use function DI\get as DIGet;
use function DI\create;
use function file_exists;
use function file_get_contents;
use function is_array;
use function is_dir;
use function is_iterable;
use function is_readable;
use function iterator_to_array;

return [
    'teknoo.east.paas.di.get_array.values' => static function (): object {
        return new class () {
            /**
             * @param array<string, mixed> $defaultValues
             * @return array<string, mixed>
             */
            public function __invoke(
                ContainerInterface $container,
                string $keyName,
                array $defaultValues = [],
            ): array {
                $values = [];

                if ($container->has($keyName)) {
                    $values = $container->get($keyName);
                }

                if (!is_iterable($values) && !is_array($values)) {
                    throw new InvalidArgumentException(
                        "`$keyName` must be an array or iterable"
                    );
                }

                if (!is_array($values)) {
                    $values = iterator_to_array($values);
                }

                return $values;
            }
        };
    },

    //Loaders
    AccountLoader::class => create(AccountLoader::class)
        ->constructor(DIGet(AccountRepositoryInterface::class)),
    JobLoader::class => create(JobLoader::class)
        ->constructor(DIGet(JobRepositoryInterface::class)),
    ProjectLoader::class => create(ProjectLoader::class)
        ->constructor(DIGet(ProjectRepositoryInterface::class)),
    ClusterLoader::class => create(ClusterLoader::class)
        ->constructor(DIGet(ClusterRepositoryInterface::class)),

    //Writer
    AccountWriter::class => create(AccountWriter::class)
        ->constructor(DIGet(ManagerInterface::class), DIGet(DatesService::class)),
    ProjectWriter::class => create(ProjectWriter::class)
        ->constructor(DIGet(ManagerInterface::class), DIGet(DatesService::class)),
    JobWriter::class => create(JobWriter::class)
        ->constructor(DIGet(ManagerInterface::class), DIGet(DatesService::class)),
    ClusterWriter::class => create(ClusterWriter::class)
        ->constructor(DIGet(ManagerInterface::class), DIGet(DatesService::class)),

    //Deleting
    'teknoo.east.paas.deleting.account' => create(DeletingService::class)
        ->constructor(DIGet(AccountWriter::class), DIGet(DatesService::class)),
    'teknoo.east.paas.deleting.project' => create(DeletingService::class)
        ->constructor(DIGet(ProjectWriter::class), DIGet(DatesService::class)),
    'teknoo.east.paas.deleting.job' => create(DeletingService::class)
        ->constructor(DIGet(JobWriter::class), DIGet(DatesService::class)),
    'teknoo.east.paas.deleting.cluster' => create(DeletingService::class)
        ->constructor(DIGet(ClusterWriter::class), DIGet(DatesService::class)),

    //YamlValidator
    YamlValidator::class => static function (ContainerInterface $container): YamlValidator {
        $xsdUrl = null;

        if ($container->has('teknoo.east.paas.compilation.yaml_validation.xsd_url')) {
            $xsdUrl = $container->get('teknoo.east.paas.compilation.yaml_validation.xsd_url');
        }

        return new YamlValidator(
            'root',
            $xsdUrl,
        );
    },

    //Availability Resources Factory
    QuotaFactory::class => create(QuotaFactory::class)
        ->constructor([
            'compute' => ComputeAvailability::class,
            'memory' => MemoryAvailability::class,
        ]),

    //Compiler
    HookCompiler::class => create()
        ->constructor(DIGet(HooksCollectionInterface::class)),
    ImageCompiler::class => static function (ContainerInterface $container): ImageCompiler {
        $imagesLibrary = ($container->get('teknoo.east.paas.di.get_array.values'))(
            $container,
            'teknoo.east.paas.compilation.containers_images_library',
            [],
        );

        $rootPath = $container->get('teknoo.east.paas.root_dir');
        foreach ($imagesLibrary as &$image) {
            if (empty($image['path']) || is_dir($image['path'])) {
                throw new MissingAttributeException('Missing path');
            }

            $image['path'] = $rootPath . $image['path'];
        }

        return new ImageCompiler($imagesLibrary);
    },
    IngressCompiler::class => static function (ContainerInterface $container): IngressCompiler {
        return new IngressCompiler(
            ($container->get('teknoo.east.paas.di.get_array.values'))(
                $container,
                'teknoo.east.paas.compilation.ingresses_extends.library',
                [],
            )
        );
    },
    MapCompiler::class => create(),
    PodCompiler::class => static function (ContainerInterface $container): PodCompiler {
        $podslibrary = ($container->get('teknoo.east.paas.di.get_array.values'))(
            $container,
            'teknoo.east.paas.compilation.pods_extends.library',
            [],
        );
        $containerslibrary = ($container->get('teknoo.east.paas.di.get_array.values'))(
            $container,
            'teknoo.east.paas.compilation.containers_extends.library',
            [],
        );

        return new PodCompiler($podslibrary, $containerslibrary);
    },

    DefaultsCompiler::class => static function (ContainerInterface $container): DefaultsCompiler {
        $storageProvider = null;
        if ($container->has('teknoo.east.paas.default_storage_provider')) {
            $storageProvider = $container->get('teknoo.east.paas.default_storage_provider');
        }

        $storageSize = null;
        if ($container->has('teknoo.east.paas.default_storage_size')) {
            $storageSize = $container->get('teknoo.east.paas.default_storage_size');
        }

        $defaultOciRegistryConfig = null;
        if ($container->has('teknoo.east.paas.default_oci_registry_config_name')) {
            $defaultOciRegistryConfig = $container->get('teknoo.east.paas.default_oci_registry_config_name');
        }

        return new DefaultsCompiler(
            storageIdentifier: $storageProvider,
            storageSize: $storageSize,
            defaultOciRegistryConfig: $defaultOciRegistryConfig,
        );
    },

    FeaturesRequirementCompiler::class => static function (ContainerInterface $container): FeaturesRequirementCompiler {
        $checkers = [];
        if ($container->has('teknoo.east.paas.compilation.features_requirement.list')) {
            $checkers = $container->get('teknoo.east.paas.compilation.features_requirement.list');
        }

        return new FeaturesRequirementCompiler($checkers);
    },

    QuotaCompiler::class => create()
        ->constructor(DIGet(QuotaFactory::class)),

    SecretCompiler::class => create(),
    ServiceCompiler::class => static function (ContainerInterface $container): ServiceCompiler {
        return new ServiceCompiler(
            ($container->get('teknoo.east.paas.di.get_array.values'))(
                $container,
                'teknoo.east.paas.compilation.services_extends.library',
                [],
            )
        );
    },
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

        $collection->add('[paas][requires]', $container->get(FeaturesRequirementCompiler::class));
        $collection->add('[paas][quotas]', $container->get(QuotaCompiler::class));
        $collection->add('[defaults]', $container->get(DefaultsCompiler::class));
        $collection->add('[maps]', $container->get(MapCompiler::class));
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
    CompiledDeploymentFactoryInterface::class => DIGet(CompiledDeploymentFactory::class),
    CompiledDeploymentFactory::class => static function (ContainerInterface $container): CompiledDeploymentFactory {
        $xsdFilePath = __DIR__ . '/Contracts/Configuration/paas_validation.xsd';

        if ($container->has('teknoo.east.paas.compilation.yaml_validation.xsd_file')) {
            $xsdFilePath = $container->get('teknoo.east.paas.compilation.yaml_validation.xsd_file');
        }

        if (!file_exists($xsdFilePath) || !is_readable($xsdFilePath)) {
            throw new InvalidArgumentException(
                "The XSD validation file '$xsdFilePath' does not exist or is not readable."
            );
        }

        $xsdFileContent = (string) file_get_contents($xsdFilePath);

        return new CompiledDeploymentFactory(
            CompiledDeployment::class,
            $xsdFileContent
        );
    },

    ConductorInterface::class => DIGet(Conductor::class),
    Conductor::class => static function (ContainerInterface $container): Conductor {
        return new Conductor(
            $container->get(CompiledDeploymentFactoryInterface::class),
            $container->get(PropertyAccessorInterface::class),
            $container->get(YamlParserInterface::class),
            $container->get(YamlValidator::class),
            $container->get(QuotaFactory::class),
            $container->get(CompilerCollectionInterface::class),
        );
    },

    Directory::class => create(),

    //Recipes steps
    //History
    StepAddHistory::class => create(),
    DeserializeHistory::class => create()
        ->constructor(
            DIGet(DeserializerInterface::class),
        ),
    ReceiveHistory::class => create(),

    //Job
    CreateNewJob::class => create(),
    DeserializeJob::class => static function (ContainerInterface $container): DeserializeJob {
        return new DeserializeJob(
            $container->get(DeserializerInterface::class),
            ($container->get('teknoo.east.paas.di.get_array.values'))(
                $container,
                'teknoo.east.paas.compilation.global_variables',
                [],
            ),
        );
    },
    GetJob::class => create()
        ->constructor(
            DIGet(JobLoader::class),
        ),
    PrepareJob::class => static function (ContainerInterface $container): PrepareJob {
        $preferRealDate = true;
        if (true === $container->has('teknoo.east.paas.symfony.prepare-job.prefer-real-date')) {
            $preferRealDate = (bool) $container->get('teknoo.east.paas.symfony.prepare-job.prefer-real-date');
        }

        return new PrepareJob(
            dateTimeService: $container->get(DatesService::class),
            errorFactory: $container->get(ErrorFactoryInterface::class),
            preferRealDate: $preferRealDate,
        );
    },

    ReceiveJob::class => create(),
    SaveJob::class => create()
        ->constructor(DIGet(JobWriter::class)),
    SerializeJob::class => static function (ContainerInterface $container): SerializeJob {
        return new SerializeJob(
            $container->get(SerializerInterface::class),
        );
    },

    //Job
    GetVariables::class => create(),
    Ping::class => create()
        ->constructor(
            DIGet(PingServiceInterface::class)
        ),
    DispatchError::class => create()
        ->constructor(
            DIGet(ErrorFactoryInterface::class),
        ),
    SetTimeLimit::class => static function (ContainerInterface $container): SetTimeLimit {
        $seconds = 5 * 60;
        if ($container->has('teknoo.east.paas.worker.time_limit')) {
            $seconds = (int) $container->get('teknoo.east.paas.worker.time_limit');
        }

        return new SetTimeLimit(
            $container->get(TimeoutServiceInterface::class),
            $seconds,
        );
    },
    UnsetTimeLimit::class => create()
        ->constructor(
            DIGet(TimeoutServiceInterface::class),
        ),

    //Project
    GetEnvironment::class => create(),
    GetProject::class => create()
        ->constructor(
            DIGet(ProjectLoader::class),
        ),

    //Worker
    BuildImages::class => create()
        ->constructor(
            DIGet(DHI::class),
        ),
    BuildVolumes::class => create()
        ->constructor(
            DIGet(DHI::class),
        ),
    CloneRepository::class => create(),
    CompileDeployment::class => create(),
    ConfigureCloningAgent::class => create()
        ->constructor(
            DIGet(CloningAgentInterface::class),
        ),
    ConfigureConductor::class => create()
        ->constructor(DIGet(ConductorInterface::class)),
    ConfigureImagesBuilder::class => create()
        ->constructor(
            DIGet(BuilderInterface::class),
        ),
    ConfigureClusterClient::class => create()
        ->constructor(
            DIGet(Directory::class),
        ),
    Deploying::class => create()
        ->constructor(
            DIGet(DHI::class),
        ),
    Exposing::class => create()
        ->constructor(
            DIGet(DHI::class),
        ),
    HookingDeployment::class => create()
        ->constructor(
            DIGet(DHI::class),
        ),
    PrepareWorkspace::class => create()
        ->constructor(DIGet(JobWorkspaceInterface::class)),
    ReadDeploymentConfiguration::class => create(),

    SerialGenerator::class => create(),

    //Base recipe
    OriginalRecipeInterface::class => DIGet(Recipe::class),
    Recipe::class => create(),

    //Plans
    NewAccountEndPointInterface::class => DIGet(NewAccountEndPoint::class),
    NewAccountEndPoint::class => static function (
        ContainerInterface $container
    ): NewAccountEndPoint {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorTemplate = $container->get('teknoo.east.common.get_default_error_template');

        return new NewAccountEndPoint(
            $container->get(OriginalRecipeInterface::class),
            $container->get(CreateObject::class),
            $container->get(FormHandlingInterface::class),
            $container->get(FormProcessingInterface::class),
            $container->get(SaveObject::class),
            $container->get(RedirectClientInterface::class),
            $container->get(RenderFormInterface::class),
            $container->get(RenderError::class),
            $accessControl,
            $defaultErrorTemplate,
        );
    },

    EditAccountEndPointInterface::class => static function (
        ContainerInterface $container
    ): EditAccountEndPointInterface {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorTemplate = $container->get('teknoo.east.common.get_default_error_template');

        return new class (
            $container->get(OriginalRecipeInterface::class),
            $container->get(LoadObject::class),
            $container->get(FormHandlingInterface::class),
            $container->get(FormProcessingInterface::class),
            $container->get(SaveObject::class),
            $container->get(RenderFormInterface::class),
            $container->get(RenderError::class),
            $accessControl,
            $defaultErrorTemplate,
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

        $defaultErrorTemplate = $container->get('teknoo.east.common.get_default_error_template');

        return new class (
            $container->get(OriginalRecipeInterface::class),
            $container->get(LoadObject::class),
            $container->get(FormHandlingInterface::class),
            $container->get(FormProcessingInterface::class),
            $container->get(SaveObject::class),
            $container->get(RenderFormInterface::class),
            $container->get(RenderError::class),
            $accessControl,
            $defaultErrorTemplate,
        ) extends AbstractEditObjectEndPoint implements EditProjectEndPointInterface {
        };
    },

    NewProjectEndPointInterface::class => DIGet(NewProjectEndPoint::class),
    NewProjectEndPoint::class => static function (ContainerInterface $container): NewProjectEndPoint {
        $accessControl = null;
        if ($container->has(ObjectAccessControlInterface::class)) {
            $accessControl = $container->get(ObjectAccessControlInterface::class);
        }

        $defaultErrorTemplate = $container->get('teknoo.east.common.get_default_error_template');

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
            $defaultErrorTemplate,
        );
    },

    NewJobInterface::class => DIGet(NewJob::class),
    NewJob::class => create()
        ->constructor(
            DIGet(OriginalRecipeInterface::class),
            DIGet(Ping::class),
            DIGet(SetTimeLimit::class),
            DIGet(GetProject::class),
            DIGet(GetEnvironment::class),
            DIGet(GetVariables::class),
            DIGet(CreateNewJob::class),
            DIGet(PrepareJob::class),
            DIGet(SaveJob::class),
            DIGet(SerializeJob::class),
            DIGet(DispatchJobInterface::class),
            DIGet(SendJobInterface::class),
            DIGet(UnsetTimeLimit::class),
            DIGet(DispatchError::class),
        ),

    AddHistoryInterface::class => DIGet(AddHistory::class),
    AddHistory::class => create()
        ->constructor(
            DIGet(OriginalRecipeInterface::class),
            DIGet(Ping::class),
            DIGet(SetTimeLimit::class),
            DIGet(ReceiveHistory::class),
            DIGet(DeserializeHistory::class),
            DIGet(GetProject::class),
            DIGet(GetJob::class),
            DIGet(StepAddHistory::class),
            DIGet(SaveJob::class),
            DIGet(SendHistoryInterface::class),
            DIGet(UnsetTimeLimit::class),
            DIGet(DispatchError::class),
        ),

    RunJobInterface::class => DIGet(RunJob::class),
    RunJob::class => create()
        ->constructor(
            DIGet(OriginalRecipeInterface::class),
            DIGet(DHI::class),
            DIGet(Ping::class),
            DIGet(SetTimeLimit::class),
            DIGet(ReceiveJob::class),
            DIGet(DeserializeJob::class),
            DIGet(PrepareWorkspace::class),
            DIGet(ConfigureCloningAgent::class),
            DIGet(CloneRepository::class),
            DIGet(ConfigureConductor::class),
            DIGet(ReadDeploymentConfiguration::class),
            DIGet(CompileDeployment::class),
            DIGet(HookingDeployment::class),
            DIGet(ConfigureImagesBuilder::class),
            DIGet(BuildImages::class),
            DIGet(BuildVolumes::class),
            DIGet(ConfigureClusterClient::class),
            DIGet(Deploying::class),
            DIGet(Exposing::class),
            DIGet(DRI::class),
            DIGet(UnsetTimeLimit::class),
            DIGet(SendHistoryInterface::class),
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

            public function fill(OriginalRecipeInterface $recipe): PlanInterface
            {
                $this->getRunJob()->fill($recipe);

                return $this;
            }

            public function add(callable|Step|BowlInterface $action, int $position): EditablePlanInterface
            {
                $this->getRunJob()->add($action, $position);

                return $this;
            }

            public function addErrorHandler(callable $handler): EditablePlanInterface
            {
                $this->getRunJob()->addErrorHandler($handler);

                return $this;
            }
        };
    },
];
