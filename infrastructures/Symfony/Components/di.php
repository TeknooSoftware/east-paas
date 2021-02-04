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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Manager\Manager;
use Teknoo\East\FoundationBundle\Command\Client;
use Teknoo\East\Paas\Contracts\Recipe\Cookbook\RunJobInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\RunJobCommand;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayHistory;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps\DisplayResult;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\PropertyAccessor;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker\DispatchJob;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Deserializer;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Serializer;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Symfony\Component\Yaml\Parser;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Website\Service\DatesService;

use function DI\create;
use function DI\get;
use function DI\value;

return [
    'teknoo.east.paas.symfony.command.run_job.name' => value('teknoo:paas:run_job'),
    'teknoo.east.paas.symfony.command.run_job.description' => value(
        'Run job manually from json file, without PaaS server'
    ),

    RunJobCommand::class => create()
        ->constructor(
            get('teknoo.east.paas.symfony.command.run_job.name'),
            get('teknoo.east.paas.symfony.command.run_job.description'),
            create(Manager::class),
            create(Client::class),
            get(RunJobInterface::class . ':proxy'),
            get(ServerRequestFactoryInterface::class),
            get(StreamFactoryInterface::class)
        ),

    DispatchJobInterface::class => get(DispatchJob::class),

    SymfonyPropertyAccessor::class => get('teknoo.east.paas.symfony.property_accessor'),
    PropertyAccessorInterface::class => get(PropertyAccessor::class),
    PropertyAccessor::class => create()
        ->constructor(get(SymfonyPropertyAccessor::class)),

    Parser::class => create(),
    YamlParserInterface::class => get(YamlParser::class),
    YamlParser::class => create()
        ->constructor(get(Parser::class)),

    DeserializerInterface::class => get(Deserializer::class),
    NormalizerInterface::class => get(Normalizer::class),
    SerializerInterface::class => get(Serializer::class),

    DisplayHistory::class => create()
        ->constructor(
            get(DatesService::class)
        ),

    DisplayResult::class => create()
        ->constructor(
            get(DatesService::class),
            get(NormalizerInterface::class)
        )
];
