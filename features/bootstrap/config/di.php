<?php

use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UriFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Client\ClientInterface as PsrClient;
use Teknoo\East\Diactoros\ResponseMessageFactory;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;

use function DI\create;
use function DI\get;

return [
    'teknoo.east.paas.worker.global_variables' => [
        'ROOT' => \dirname(__DIR__)
    ],
    'teknoo.east.paas.root_dir' => __DIR__ . '../../',
    'teknoo.east.paas.conductor.images_library' => [
        'php-run-74' => [
            'build-name' => 'php-run',
            'tag' => '7.4',
            'path' => '/library/php-run/7.4/',
        ],
    ],
    'teknoo.east.paas.default_storage_provider' => 'nfs',
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
];
