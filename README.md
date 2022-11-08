Teknoo Software - PaaS library
==============================

[![Latest Stable Version](https://poser.pugx.org/teknoo/east-paas/v/stable)](https://packagist.org/packages/teknoo/east-paas)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/east-paas/v/unstable)](https://packagist.org/packages/teknoo/east-paas)
[![Total Downloads](https://poser.pugx.org/teknoo/east-paas/downloads)](https://packagist.org/packages/teknoo/east-paas)
[![License](https://poser.pugx.org/teknoo/east-paas/license)](https://packagist.org/packages/teknoo/east-paas)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

Universal package, following the #East programming philosophy, build on Teknoo/East-Foundation (and Teknoo/Recipe) 
to implement a custom PaaS manager like platform.sh, compatible with Docker or any OCI implementation and Kubernetes.

Example with Symfony
--------------------

    //config/packages/di_bridge.yaml:
    di_bridge:
        definitions:
          - '%kernel.project_dir%/config/di.php'
    
    //config/packages/east_foundation.yaml:
    di_bridge:
        definitions:
            - '%kernel.project_dir%/vendor/teknoo/east-foundation/src/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-foundation/infrastructures/symfony/Resources/config/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-foundation/infrastructures/symfony/Resources/config/laminas_di.php'
        import:
            Psr\Log\LoggerInterface: 'logger'

    //config/packages/east_website_di.yaml:
    di_bridge:
        definitions:
            - '%kernel.project_dir%/vendor/teknoo/east-website/src/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/symfony/Resources/config/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/symfony/Resources/config/laminas_di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/di.php'
        import:
            Doctrine\Persistence\ObjectManager: 'doctrine_mongodb.odm.default_document_manager'
    
    //config/packages/east_paas_di.yaml:
    di_bridge:
        definitions:
            - '%kernel.project_dir%/vendor/teknoo/east-paas/src/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Doctrine/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Flysystem/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Git/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Kubernetes/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Image/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Composer/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Laminas/di.php'
            - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Symfony/Components/di.php'
      
    //bundles.php
    ...
    Teknoo\DI\SymfonyBridge\DIBridgeBundle::class => ['all' => true],
    Teknoo\East\FoundationBundle\EastFoundationBundle::class => ['all' => true],
    Teknoo\East\CommonBundle\TeknooEastCommonBundle::class => ['all' => true],
    Teknoo\East\Paas\Infrastructures\EastPaasBundle\TeknooEastPaasBundle::class => ['all' => true],
    
    //In doctrine config
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

    //In security.yaml
    security:
      //..
      providers:
        main:
          id: 'teknoo.east.website.bundle.user_provider'

    //In routing.yaml
    paas_admin_account:
        resource: '@TeknooEastPaasBundle/Resources/config/routing_admin_account.yaml'
        prefix: '/admin'
        schemes:    [https]
    
    paas_admin_job:
        resource: '@TeknooEastPaasBundle/Resources/config/routing_admin_job.yaml'
        prefix: '/admin'
        schemes:    [https]

    //in config/di.php
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

Example of **.paas.yaml** configuration file present into git repository to deploy
---------------------------------------------------------------------------------

Project demo available [here](https://github.com/TeknooSoftware/east-paas-project-demo).
   
    paas: #Dedicated to compiler
      version: v1
    
    #Config
    maps:
      map1:
        key1: value1
        key2: ${FOO}
      map2:
        foo: bar
        bar: foo
    
    #Secrets provider
    secrets:
      map-vault:
        provider: map #Internal secrets, must be passed in this file
        options:
          key1: value1
          key2: ${FOO}
      map-vault2:
        provider: map #Internal secrets, must be passed in this file
        options:
          hello: world
      volume-vault:
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
        add: #folder or file, from .paas.yaml where is located to add to the volume
          - 'extra'
      other_name: #Name of the volume
        add: #folder or file, from .paas.yaml where is located to add to the volume
          - 'vendor'
    
    #Pods (set of container)
    pods:
      php-pods: #podset name
        replicas: 2 #instance of pods
        containers:
          php-run: #Container name
            image: ${PHP_IMAGE} #Container image to use
            version: ${PHP_VERSION}
            listen: #Port listen by the container
              - 8080
            volumes: #Volumes to link
              extra:
                from: 'extra'
                mount-path: '/opt/extra' #Path where volume will be mount
              app:
                mount-path: '/opt/app' #Path where data will be stored
                add: #folder or file, from .paas.yaml where is located to add to the volume
                  - 'src'
                  - 'vendor'
                  - 'composer.json'
                  - 'composer.lock'
              data: #Persistent volume, can not be pre-populated
                mount-path: '/opt/data'
                persistent: true
              map:
                mount-path: '/map'
                from-map: 'map2'
              vault:
                mount-path: '/vault'
                from-secret: 'volume-vault'
            variables: #To define some environment variables
              SERVER_SCRIPT: '/opt/app/src/server.php'
              from-maps:
                KEY0: 'map1.key0'
              import-maps:
                - 'map2'
              from-secrets: #To fetch some value from secret/vault
                KEY1: 'map-vault.key1'
                KEY2: 'map-vault.key2'
              import-secrets:
                - 'map-vault2'
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
        host: ${PROJECT_URL}
        tls:
          secret: "tls-vault" #Configure the orchestrator to fetch value from vault
        service: #default service
          name: demo-service
          port: 8080
        paths:
          - path: /php
            service:
              name: php-service
              port: 9876


Support this project
---------------------

This project is free and will remain free, but it is developed on my personal time. 
If you like it and help me maintain it and evolve it, don't hesitate to support me on [Patreon](https://patreon.com/teknoo_software).
Thanks :) Richard. 

Installation & Requirements
---------------------------
To install this library with composer, run this command :

    composer require teknoo/east-paas

This library requires :

    * PHP 8.1+
    * A PHP autoloader (Composer is recommended)
    * Teknoo/Immutable.
    * Teknoo/States.
    * Teknoo/Recipe.
    * Teknoo/East-Foundation.
    * Teknoo/East-Website.
    * Optional: Symfony 6.1+ (for administration)

Credits
-------
Richard Déloge - <richarddeloge@gmail.com> - Lead developer.
Teknoo Software - <https://teknoo.software>

About Teknoo Software
---------------------
**Teknoo Software** is a PHP software editor, founded by Richard Déloge.
Teknoo Software's goals : Provide to our partners and to the community a set of high quality services or software,
 sharing knowledge and skills.

License
-------
East PaaS is licensed under the MIT License - see the licenses folder for details

Contribute :)
-------------

You are welcome to contribute to this project. [Fork it on Github](CONTRIBUTING.md)
