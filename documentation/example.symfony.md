Teknoo Software - PaaS library
==============================

With Symfony Recipe
-------------------

Run the command `composer require teknoo/east-paas-symfony` and follow instructions.

Without Symfony Recipe
----------------------

### config/packages/di_bridge.yaml

    di_bridge:
        definitions:
          - '%kernel.project_dir%/config/di.php'

### config/packages/east_foundation.yaml

    di_bridge:
        definitions:
            - '%kernel.project_dir%/vendor/teknoo/east-foundation/src/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-foundation/infrastructures/symfony/Resources/config/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-foundation/infrastructures/symfony/Resources/config/laminas_di.php'
        import:
            Psr\Log\LoggerInterface: 'logger'

### config/packages/east_common_di.yaml

    di_bridge:
        definitions:
            - '%kernel.project_dir%/vendor/teknoo/east-common/src/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-common/infrastructures/doctrine/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-common/infrastructures/symfony/Resources/config/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-common/infrastructures/symfony/Resources/config/laminas_di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-common/infrastructures/di.php'
        import:
            Doctrine\Persistence\ObjectManager: 'doctrine_mongodb.odm.default_document_manager'

### config/packages/east_paas_di.yaml

    di_bridge:
        definitions:
            - '%kernel.project_dir%/src/di.php'
            - '%kernel.project_dir%/infrastructures/Doctrine/di.php'
            - '%kernel.project_dir%/infrastructures/Flysystem/di.php'
            - '%kernel.project_dir%/infrastructures/Git/di.php'
            - '%kernel.project_dir%/infrastructures/Kubernetes/di.php'
            - '%kernel.project_dir%/infrastructures/Image/di.php'
            - '%kernel.project_dir%/infrastructures/ProjectBuilding/di.php'
            - '%kernel.project_dir%/infrastructures/PhpSecLib/di.php'
            - '%kernel.project_dir%/infrastructures/Symfony/Components/di.php'
            - '%kernel.project_dir%/infrastructures/Laminas/di.php'

### bundles.php

    ...
    Teknoo\DI\SymfonyBridge\DIBridgeBundle::class => ['all' => true],
    Teknoo\East\FoundationBundle\EastFoundationBundle::class => ['all' => true],
    Teknoo\East\CommonBundle\TeknooEastCommonBundle::class => ['all' => true],
    Teknoo\East\Paas\Infrastructures\EastPaasBundle\TeknooEastPaasBundle::class => ['all' => true],

### In doctrine config

    doctrine_mongodb:
        connections:
            default:
                server: "%env(MONGODB_SERVER)%"
                options: {}
        default_database: '%env(MONGODB_NAME)%'
        document_managers:
            default:
                auto_mapping: true
                mappings:
                    TeknooEastPaas:
                        type: 'xml'
                        dir: '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Doctrine/config/universal'
                        is_bundle: false
                        prefix: 'Teknoo\East\Paas\Object'
                    TeknooEastPaasInfrastructuresDoctrine:
                        type: 'xml'
                        dir: '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Doctrine/config/odm'
                        is_bundle: false
                        prefix: 'Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM'
                    AppObjectPersisted:
                        type: 'xml'
                        dir: '%kernel.project_dir%/config/doctrine'
                        is_bundle: false
                        prefix: 'App\Object\Persisted'

### In routing.yaml

    paas_admin_account:
        resource: '@TeknooEastPaasBundle/Resources/config/routing_admin_account.yaml'
        prefix: '/admin'
        schemes:    [https]
    
    paas_admin_job:
        resource: '@TeknooEastPaasBundle/Resources/config/routing_admin_job.yaml'
        prefix: '/admin'
        schemes:    [https]

### In PHP-DI

    return [
        //Hook
        HooksCollectionInterface::class => ...

        //Message
        MessageFactoryInterface::class => get(MessageFactory::class),

        //OCI libraries
        'teknoo.east.paas.conductor.images_library' => [...]

        //variables
        'teknoo.east.paas.root_dir' => ...,
    
        'teknoo.east.paas.default_storage_provider' => ...,
    
        'teknoo.east.paas.worker.tmp_dir' => ...,
        'teknoo.east.paas.worker.global_variables' => [...],
    
        'teknoo.east.paas.composer.phar.path' => ...,
    
        'teknoo.east.paas.img_builder.cmd' => ...,
        'teknoo.east.paas.img_builder.build.timeout' => ...,
        'teknoo.east.paas.img_builder.build.platforms' => ...,
    
        'teknoo.east.paas.kubernetes.ssl.verify' => ...,
    ];
