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

namespace Teknoo\Tests\East\Paas\Parser;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser;
use Teknoo\East\Paas\Parser\YamlValidator;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Parser\YamlValidator
 */
class YamlValidatorTest extends TestCase
{
    private function buildValidator(): YamlValidator
    {
        return new YamlValidator('root');
    }

    private function getYamlArray(): array
    {
        $conf = <<<'EOF'
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
    type: foo
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
    composer: 
      action: install #Hook to call
      arguments:
        - 'no-dev'
        - 'optimize-autoloader'
        - 'classmap-authoritative'
  custom-hook:
    hook-id: foo bar

#Volume to build to use with container
volumes:
  extra: #Name of the volume
    local-path: "/foo/bar" #optional local path where store data in the volume
    add: #folder or file, from .paas.yaml where is located to add to the volume
      - 'extra'
  other-name: #Name of the volume
    add: #folder or file, from .paas.yaml where is located to add to the volume
      - 'vendor'

#Pods (set of container)
pods:
  php-pods: #podset name
    replicas: 2 #instance of pods
    upgrade:
        max-upgrading-pods: 2
        max-unavailable-pods: 1
    containers:
      php-run: #Container name
        image: registry.teknoo.software/php-run #Container image to use
        version: 7.4
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
              - 'var'
              - 'vendor'
              - 'composer.json'
              - 'composer.lock'
              - 'composer.phar'
            writables:
              - 'var/*'
          data: #Persistent volume, can not be pre-populated
            mount-path: '/opt/data'
            persistent: true
            storage-size: 3Gi
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
        healthcheck:
          initial-delay-seconds: 15
          period-seconds: 35
          probe:
            command: ['ps', 'aux', 'php']
  demo:
    replicas: 1
    containers:
      nginx:
        image: registry.hub.docker.com/library/nginx
        version: alpine
        listen: #Port listen by the container
          - 8080
          - 8181
        volumes:
          www:
            mount-path: '/var'
            add:
              - 'nginx/www'
          config:
            mount-path: '/etc/nginx/conf.d/'
            add:
              - 'nginx/conf.d/default.conf'
        healthcheck:
          initial-delay-seconds: 10
          period-seconds: 30
          probe:
            http:
              port: 8080
              path: '/status' 
      blackfire:
        image: 'blackfire/blackfire'
        version: '2'
        listen:
          - 8307
        variables:
          BLACKFIRE_SERVER_ID: 'foo'
          BLACKFIRE_SERVER_TOKEN: 'bar'

#Pods expositions
services:
  php-service: #Service name
    pod: "php-pods" #Pod name, use service name by default
    internal: false #If false, a load balancer is use to access it from outside
    protocol: 'TCP' #Or UDP
    ports:
      - listen: 9876 #Port listened
        target: 8080 #Pod's port targeted
  demo: #Service name
    ports:
      - listen: 8080 #Port listened
        target: 8080 #Pod's port targeted
      - listen: 8181 #Port listened
        target: 8181 #Pod's port targeted

#Ingresses configuration
ingresses:
  demo: #rule name
    host: demo-paas.teknoo.software
    tls:
      secret: "demo-vault" #Configure the orchestrator to fetch value from vault
    service: #default service
      name: demo
      port: 8080
    paths:
      - path: /php
        service:
          name: php-service
          port: 9876
  demo-secure: #rule name
    host: demo-secure.teknoo.software
    https-backend: true
    tls:
      secret: "demo-vault" #Configure the orchestrator to fetch value from vault
    service: #default service
      name: demo
      port: 8181

EOF;

        return (new Parser())->parse($conf);
    }

    private function getXsdFile(): string
    {
        $fileName = \dirname(__DIR__, 3) . '/src/Contracts/Configuration/paas_validation.xsd';

        return \file_get_contents($fileName);
    }

    public function testValidConf()
    {
        $configuration = $this->getYamlArray();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success')->with($configuration);
        $promise->expects(self::never())->method('fail');

        $xsd = $this->getXsdFile();

        self::assertInstanceOf(
            YamlValidator::class,
            $this->buildValidator()->validate(
                $configuration,
                $xsd,
                $promise
            )
        );
    }
    public function testNotValidConfWithNonValidName()
    {
        $configuration = $this->getYamlArray();
        $configuration['services']['php_service'] = $configuration['services']['php-service'];
        unset($configuration['services']['php-service']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $xsd = $this->getXsdFile();

        self::assertInstanceOf(
            YamlValidator::class,
            $this->buildValidator()->validate(
                $configuration,
                $xsd,
                $promise
            )
        );
    }

    public function testNotValidConfWithMissingParts()
    {
        $configuration = $this->getYamlArray();
        unset($configuration['pods']);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $xsd = $this->getXsdFile();

        self::assertInstanceOf(
            YamlValidator::class,
            $this->buildValidator()->validate(
                $configuration,
                $xsd,
                $promise
            )
        );
    }
}
