<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony;

use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\PropertyAccessor;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker\DispatchJob;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Deserializer;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Serializer;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Symfony\Component\Yaml\Parser;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;

use function DI\create;
use function DI\get;

return [
    DispatchJobInterface::class => get(DispatchJob::class),
    DispatchJob::class => static function (ContainerInterface $container): DispatchJob {
        return new DispatchJob(
            $container->get('messenger.default_bus')
        );
    },

    SymfonyPropertyAccessor::class => get('property_accessor'),
    PropertyAccessorInterface::class => get(PropertyAccessor::class),
    PropertyAccessor::class => create()
        ->constructor(get(SymfonyPropertyAccessor::class)),

    Parser::class => create(),
    YamlParserInterface::class => get(YamlParser::class),
    YamlParser::class => create()
        ->constructor(get(Parser::class)),

    DeserializerInterface::class => get(Deserializer::class),
    Deserializer::class => create()
        ->constructor(get('serializer')),

    NormalizerInterface::class => get(Normalizer::class),
    Normalizer::class => create()
        ->constructor(get('serializer')),

    SerializerInterface::class => get(Serializer::class),
    Serializer::class => create()
        ->constructor(get('serializer')),
];
