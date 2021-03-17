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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony;

use DI\Container;
use DI\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Symfony\Component\Yaml\Parser;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\PropertyAccessor;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker\DispatchJob;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Deserializer;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Serializer;
use Teknoo\East\Website\Service\DatesService;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ContainerTest extends TestCase
{
    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__.'/../../../../infrastructures/Symfony/Components/di.php');

        return $containerDefinition->build();
    }

    public function testDispatchJobInterface()
    {
        $container = $this->buildContainer();
        $container->set(
            DispatchJob::class,
            $this->createMock(DispatchJob::class)
        );

        self::assertInstanceOf(
            DispatchJobInterface::class,
            $container->get(DispatchJobInterface::class)
        );
    }

    public function testPropertyAccessorInterface()
    {
        $container = $this->buildContainer();
        $container->set(
            'teknoo.east.paas.symfony.property_accessor',
            $this->createMock(SymfonyPropertyAccessor::class)
        );

        self::assertInstanceOf(
            PropertyAccessorInterface::class,
            $container->get(PropertyAccessorInterface::class)
        );
    }

    public function testPropertyAccessor()
    {
        $container = $this->buildContainer();
        $container->set(
            'teknoo.east.paas.symfony.property_accessor',
            $this->createMock(SymfonyPropertyAccessor::class)
        );

        self::assertInstanceOf(
            PropertyAccessor::class,
            $container->get(PropertyAccessor::class)
        );
    }

    public function testParser()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            Parser::class,
            $container->get(Parser::class)
        );
    }

    public function testYamlParserInterface()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            YamlParserInterface::class,
            $container->get(YamlParserInterface::class)
        );
    }

    public function testYamlParser()
    {
        $container = $this->buildContainer();

        self::assertInstanceOf(
            YamlParser::class,
            $container->get(YamlParser::class)
        );
    }

    public function testDeserializerInterface()
    {
        $container = $this->buildContainer();
        $container->set(Deserializer::class, $this->createMock(Deserializer::class));

        self::assertInstanceOf(
            DeserializerInterface::class,
            $container->get(DeserializerInterface::class)
        );
    }

    public function testNormalizerInterface()
    {
        $container = $this->buildContainer();
        $container->set(Normalizer::class, $this->createMock(Normalizer::class));

        self::assertInstanceOf(
            NormalizerInterface::class,
            $container->get(NormalizerInterface::class)
        );
    }

    public function testSerializerInterface()
    {
        $container = $this->buildContainer();
        $container->set(Serializer::class, $this->createMock(Serializer::class));

        self::assertInstanceOf(
            SerializerInterface::class,
            $container->get(SerializerInterface::class)
        );
    }

    public function testDisplayHistory()
    {
        $container = $this->buildContainer();
        $container->set(DatesService::class, $this->createMock(DatesService::class));

        self::assertInstanceOf(
            DisplayHistoryHandler::class,
            $container->get(DisplayHistoryHandler::class)
        );
    }

    public function testDisplayResult()
    {
        $container = $this->buildContainer();
        $container->set(DatesService::class, $this->createMock(DatesService::class));
        $container->set(NormalizerInterface::class, $this->createMock(NormalizerInterface::class));

        self::assertInstanceOf(
            DisplayResultHandler::class,
            $container->get(DisplayResultHandler::class)
        );
    }
}
