<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Symfony\Component\Yaml\Parser;
use Teknoo\East\Foundation\Command\Executor;
use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Recipe\Plan\RunJobInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Worker\DispatchJobInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Command\RunJobCommand;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\PropertyAccessor;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayHistoryHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command\DisplayResultHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Recipe\Step\Worker\DispatchJob;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Deserializer;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Normalizer;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Serializer;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
        $containerDefinition->addDefinitions(
            [
                Executor::class => function () {
                    return $this->createMock(Executor::class);
                }
            ],
        );
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
        $container->set(EncryptionInterface::class, $this->createMock(EncryptionInterface::class));

        self::assertInstanceOf(
            DisplayHistoryHandler::class,
            $container->get(DisplayHistoryHandler::class)
        );
    }

    public function testDisplayResult()
    {
        $container = $this->buildContainer();
        $container->set(EncryptionInterface::class, $this->createMock(EncryptionInterface::class));

        self::assertInstanceOf(
            DisplayResultHandler::class,
            $container->get(DisplayResultHandler::class)
        );
    }

    public function testRunJobCommand()
    {
        $container = $this->buildContainer();
        $container->set(ServerRequestFactoryInterface::class, $this->createMock(ServerRequestFactoryInterface::class));
        $container->set(StreamFactoryInterface::class, $this->createMock(StreamFactoryInterface::class));
        $container->set(MessageFactoryInterface::class, $this->createMock(MessageFactoryInterface::class));
        $container->set(DatesService::class, $this->createMock(DatesService::class));
        $container->set(NormalizerInterface::class, $this->createMock(NormalizerInterface::class));
        $container->set(RunJobInterface::class . ':proxy', $this->createMock(RunJobInterface::class));
        $container->set(EncryptionInterface::class, $this->createMock(EncryptionInterface::class));

        self::assertInstanceOf(
            RunJobCommand::class,
            $container->get(RunJobCommand::class)
        );
    }
}
