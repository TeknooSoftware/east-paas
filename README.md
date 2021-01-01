Teknoo Software - PaaS library
==============================

[![Build Status](https://travis-ci.com/TeknooSoftware/east-paas.svg?branch=master)](https://travis-ci.com/TeknooSoftware/east-paas)
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

    //config/packages/east_website_di.yaml:
    di_bridge:
      definitions:
        - '%kernel.project_dir%/vendor/teknoo/east-website/src/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/symfony/Resources/config/di.php'
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
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/BuildKit/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Composer/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Symfony/Components/di.php'
        - '%kernel.project_dir%/config/di.php'
      
    //config/packages/di_bridge.yaml:
    di_bridge:
      definitions:
        - '%kernel.project_dir%/config/di.php'

    
    //bundles.php
    ...
    Teknoo\East\FoundationBundle\EastFoundationBundle::class => ['all' => true],
    Teknoo\East\WebsiteBundle\TeknooEastWebsiteBundle::class => ['all' => true],
    Teknoo\DI\SymfonyBridge\DIBridgeBundle::class => ['all' => true],
    Teknoo\East\Paas\Infrastructures\EastPaasBundle\TeknooEastPaasBundle::class => ['all' => true],

    //In doctrine config
    doctrine_mongodb:
      document_managers:
        default:
          auto_mapping: true
          mappings:
            TeknooEastWebsite:
              type: 'xml'
              dir: '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/config/universal'
              is_bundle: false
              prefix: 'Teknoo\East\Website\Object'
            TeknooEastWebsiteDoctrine:
              type: 'xml'
              dir: '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/config/doctrine'
              is_bundle: false
              prefix: 'Teknoo\East\Website\Doctrine\Object'
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

    //In messenger.yml
    framework:
      messenger:
        transports:
          app_message_job: '%env(MESSENGER_JOB_TRANSPORT_DSN)%'
    
        routing:
          Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Job: 'app_message_job'

    //In security.yml
    security:
      //..
      providers:
        main:
          id: 'teknoo.east.website.bundle.user_provider'

    //In routing.yml
    website:
      resource: '@TeknooEastWebsiteBundle/Resources/config/routing.yml'
      
    app_job_new:
      path: '/project/{projectId}/environment/{envName}/job/create'
      methods:  ['GET', 'POST', 'PUT']
      defaults: { _controller: 'teknoo.east.paas.symfony.end_point.new_job' }
    
    app_job_add_history:
      path: '/project/{projectId}/environment/{envName}/job/{jobId}/log'
      methods:  ['PUT']
      defaults: { _controller: 'teknoo.east.paas.symfony.end_point.job_add_history' }
    
    app_worker_job_run:
      path: '/project/{projectId}/environment/{envName}/job/{jobId}/run'
      methods:  ['PUT']
      defaults: { _controller: 'teknoo.east.paas.symfony.end_point.job_run' }

    //in config/di.php
    return [
       'app.paas.hostname' => env('WEBSITE_HOSTNAME', 'localhost'),
       'app.paas.job_root' => env('JOB_ROOT', \sys_get_temp_dir()),
       'app.http_client.verify_ssl' => env('HTTP_CLIENT_VERIFY_SSL', true),
       'app.http_client.timeout' => env('HTTP_CLIENT_TIMEOUT', 30),
   
       'teknoo.east.paas.worker.add_history_pattern' => function (ContainerInterface $container): string {
           return 'https://' . $container->get('app.paas.hostname') . '/project/{projectId}/environment/{envName}/job/{jobId}/log';
       },
   
       'teknoo.east.paas.worker.global_variables' => [
           'ROOT' => \dirname(__DIR__)
       ],
   
       'teknoo.east.paas.kubernetes.ssl.verify' => value(false),
       'teknoo.east.paas.buildkit.build.timeout' => value(10*60),
       'teknoo.east.paas.buildkit.builder.name' => 'paas_builderx_mono',
       'teknoo.east.paas.default_storage_provider' => 'paas-nfs-pvc',
       'teknoo.east.paas.buildkit.build.platforms' => 'linux/arm64',
    
        HostnameRedirectionMiddleware::class => function (ContainerInterface $container): HostnameRedirectionMiddleware {
        return new HostnameRedirectionMiddleware($container->get('app.paas.hostname'));
    },

    RecipeInterface::class => decorate(function ($previous, ContainerInterface $container) {
        if ($previous instanceof RecipeInterface) {
            $previous = $previous->registerMiddleware(
                $container->get(HostnameRedirectionMiddleware::class),
                4
            );
        }

        return $previous;
    }),

    'teknoo.east.paas.conductor.images_library' => [
        'php-run-74' => [
            'build-name' => 'php-run',
            'tag' => '7.4',
            'path' => '/library/php-run/7.4/',
        ],
    ],

    'teknoo.east.paas.root_dir' => \dirname(__DIR__),
    'teknoo.east.paas.worker.tmp_dir' => get('app.paas.job_root'),

    'teknoo.east.paas.composer.phar.path' => \dirname(__DIR__) . '/composer.phar',

    HooksCollectionInterface::class => static function (ContainerInterface $container): HooksCollectionInterface {
        return new class ($container) implements HooksCollectionInterface {

            private ContainerInterface $container;

            public function __construct(ContainerInterface $container)
            {
                $this->container = $container;
            }

            public function getIterator(): \Traversable {
                yield 'composer' => $this->container->get(ComposerHook::class);
            }
        };
    },

    UriFactoryInterface::class => ...
    ResponseFactoryInterface::class => ...
    RequestFactoryInterface::class => ...
    StreamFactoryInterface::class => ...

    //Misc
    ClientInterface::class => ...

Example of **.paas.yml** configuration file present into git repository to deploy
---------------------------------------------------------------------------------

Project demo available [here](https://github.com/TeknooSoftware/east-paas-project-demo).

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
        composer: ${COMPOSER} #Hook to call
    
    #Volume to build to use with container
    volumes:
      extra: #Name of the volume
        local_path: "/foo/bar" #optional local path where store data in the volume
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
            variables:
              SERVER_SCRIPT: '/opt/app/src/server.php'
    
    #Pods expositions
    services:
      php-pods: #Pod name
        - listen: 9876 #Port listened
          target: 8080 #Pod's port targeted


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

    * PHP 7.4+
    * A PHP autoloader (Composer is recommended)
    * Teknoo/Immutable.
    * Teknoo/States.
    * Teknoo/Recipe.
    * Teknoo/East-Foundation.
    * Teknoo/East-Website.
    * Optional: Symfony 4.4+ (for administration)

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
