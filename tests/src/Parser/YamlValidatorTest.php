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
    options:
      foo: bar
      bar: foo
  tls-vault:
    provider: map
    type: tls
    options:
      tls.crt: "${TLS_CERT}"
      tls.key: "${TLS_KEY}"
      ca.crt: "${TLS_CA}"

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
  extra: #Name of the volume
    local-path: "/foo/bar" #optional local path where store data in the volume
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
            add: #folder or file, from .paas.yml where is located to add to the volume
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
          import-map: 
            - 'map2'
          from-secrets: #To fetch some value from secret/vault
            KEY1: 'map-vault.key1'
            KEY2: 'map-vault.key2'
          import-secret: 
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

    public function testNotValidConf()
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
