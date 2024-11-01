<?php

namespace Teknoo\Tests\East\Paas\Behat\config;

use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Client\ClientInterface as PsrClient;
use Teknoo\East\Diactoros\ResponseMessageFactory;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;

use Teknoo\East\Paas\Contracts\Recipe\Plan\RunJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Additional\RunJobStepsInterface;
use Teknoo\Tests\East\Paas\Behat\FeatureContext;
use function DI\create;
use function DI\decorate;
use function DI\get;

return [
    'teknoo.east.paas.compilation.global_variables' => [
        'ROOT' => \dirname(__DIR__),
        'SERVER_SCRIPT' => '/opt/app/src/server.php',
    ],
    'teknoo.east.paas.root_dir' => __DIR__ . '../../',
    'teknoo.east.paas.project_configuration_filename' => '.paas.yaml',
    'teknoo.east.paas.compilation.containers_images_library' => [
        'php-run-74' => [
            'build-name' => 'php-run',
            'tag' => '7.4',
            'path' => '/library/php-run/7.4/',
        ],
    ],
    'teknoo.east.paas.kubernetes.ingress.default_annotations' => [
        'foo' => 'bar',
    ],
    UriFactoryInterface::class => get(UriFactory::class),
    UriFactory::class => create(),

    ResponseFactoryInterface::class => get(ResponseFactory::class),
    ResponseFactory::class => create(),

    RequestFactoryInterface::class => get(RequestFactory::class),
    RequestFactory::class => create(),

    StreamFactoryInterface::class => get(StreamFactory::class),
    StreamFactory::class => create(),

    //Job
    PsrClient::class => static function (): PsrClient {
        return new class () implements PsrClient {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new \Laminas\Diactoros\Response();
            }
        };
    },

    MessageFactoryInterface::class => get(ResponseMessageFactory::class),

    RunJobInterface::class => decorate(
        static function (RunJobInterface $previous): RunJobInterface {
            $previous->add(FeatureContext::compareCD(...), 89);

            return $previous;
        }
    ),
];
