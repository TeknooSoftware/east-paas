Teknoo Software - PaaS library
==============================

[![Build Status](https://travis-ci.com/TeknooSoftware/east-paas.svg?branch=master)](https://travis-ci.com/TeknooSoftware/east-paas)
[![Latest Stable Version](https://poser.pugx.org/teknoo/east-paas/v/stable)](https://packagist.org/packages/teknoo/east-paas)
[![Latest Unstable Version](https://poser.pugx.org/teknoo/east-paas/v/unstable)](https://packagist.org/packages/teknoo/east-paas)
[![Total Downloads](https://poser.pugx.org/teknoo/east-paas/downloads)](https://packagist.org/packages/teknoo/east-paas)
[![License](https://poser.pugx.org/teknoo/east-paas/license)](https://packagist.org/packages/teknoo/east-paas)
[![PHPStan](https://img.shields.io/badge/PHPStan-enabled-brightgreen.svg?style=flat)](https://github.com/phpstan/phpstan)

Universal package, following the #East programming philosophy, build on Teknoo/East-Foundation (and Teknoo/Recipe) 
to implement a custom PaaS manager like platform.sh, compatible with Docker and Kubernetes

Example with Symfony
--------------------

    //config/packages/di_bridge.yaml:
    di_bridge:
      definitions:
        - '%kernel.project_dir%/vendor/teknoo/east-foundation/src/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-foundation/infrastructures/symfony/Resources/config/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/src/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/doctrine/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/symfony/Resources/config/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-website/infrastructures/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/src/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Doctrine/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Flysystem/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Git/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Kubernetes/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Docker/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Composer/di.php'
        - '%kernel.project_dir%/vendor/teknoo/east-paas/infrastructures/Symfony/Components/di.php'
        - '%kernel.project_dir%/config/di.php'
      import:
        Doctrine\Persistence\ObjectManager: 'doctrine_mongodb.odm.default_document_manager'

    
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
    
        'teknoo.east.paas.worker.add_history_pattern' => function (ContainerInterface $container): string {
            return 'https://' . $container->get('app.paas.hostname') . '/project/{projectId}/environment/{envName}/job/{jobId}/log';
        },
    
        'teknoo.east.paas.conductor.images_library' => [
            'php-run-74' => [
                'build-name' => 'php-run',
                'tag' => '7.4',
                'path' => '/library/php-run/7.4/',
            ],
            'php-fpm-74' => [
                'build-name' => 'php-fpm',
                'tag' => '7.4',
                'path' => '/library/php-fpm/7.4/',
            ],
        ],
    
        'teknoo.east.paas.root_dir' => \dirname(__DIR__),
        'teknoo.east.paas.worker.tmp_dir' => get('app.paas.job_root'),
    
        HooksCollectionInterface::class => static function (ContainerInterface $container): HooksCollectionInterface {
            return [
                'composer' => $container->get(ComposerHook::class),
                'git' => $container->get(Git\Hook::class),
            ]
        },
    
        UriFactoryInterface::class => ...
        ResponseFactoryInterface::class => ...
        RequestFactoryInterface::class => ...
        StreamFactoryInterface::class => ...
        ClientInterface::class => ...
    ];

Example of **.paas.yml** configuration file present into git repository to deploy
---------------------------------------------------------------------------------

Project demo available [here](https://github.com/TeknooSoftware/east-paas-project-demo).

    paas: #Dedicated to compiler
      version: v1
    
    #Custom image, not available in the library
    images:
      foo:
        build-name: foo
        tag: latest
        path: '/images/${FOO}'
    
    #Hook to build the project before container, Called in this order
    builds:
      composer-build: #Name of the step
        composer: ${COMPOSER} #Hook to call
    
    #Volume to build to use with container
    volumes:
      main: #Name of the module
        target: '/opt/paas/' #Path where data will be stored into the volume
        add: #folder or file, from .paas.yml where is located to add to the volume
          - 'src'
          - 'vendor'
          - 'composer.json'
          - 'composer.lock'
          - 'composer.phar'
      other_name: #Name of the volume
        target: '/opt/vendor/' #Path where data will be stored into the volume
        add: #folder or file, from .paas.yml where is located to add to the volume
          - 'vendor'
          
    #Pods (set of container)
    pods:
      php-pods: #podset name
        replicas: 1 #instance of pods
        containers:
          foo-run:
            image: foo
            version: latest
            volumes: #Volumes to link
              - target
          php-run: #Container name
            image: php-run #Container image to use
            version: 7.4
            listen: #Port listen by the container
              - 8080
            volumes: #Volumes to link
              - main
            variables:
              SERVER_SCRIPT: '/opt/paas/src/server.php'
    
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
