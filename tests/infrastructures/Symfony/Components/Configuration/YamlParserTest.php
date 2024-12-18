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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingConfiguration;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(YamlParser::class)]
class YamlParserTest extends TestCase
{
    private ?Parser $parser = null;

    /**
     * @return Parser|MockObject
     */
    private function getParserMock(): Parser
    {
        if (!$this->parser instanceof Parser) {
            $this->parser = $this->createMock(Parser::class);
        }

        return $this->parser;
    }

    public function buildParser(): YamlParser
    {
        return new YamlParser(
            $this->getParserMock()
        );
    }

    public function testParseBadValue()
    {
        $this->expectException(\TypeError::class);
        $this->buildParser()->parse(new \stdClass(), $this->createMock(PromiseInterface::class), 1);
    }

    public function testParseBadPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildParser()->parse('foo', new \stdClass(), 1);
    }

    public function testParseBadFlag()
    {
        $this->expectException(\TypeError::class);
        $this->buildParser()->parse('foo', $this->createMock(PromiseInterface::class), new \stdClass());
    }

    public function testParseGood()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with(['foo' => 'bar']);
        $promise->expects($this->never())->method('fail');

        $this->getParserMock()
            ->expects($this->any())
            ->method('parse')
            ->willReturn(['foo' => 'bar']);

        self::assertInstanceOf(
            YamlParser::class,
            $this->buildParser()
                ->parse(
                    'foo.bar',
                    $promise,
                    123
                )
        );
    }

    public function testParseFail()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->getParserMock()
            ->expects($this->any())
            ->method('parse')
            ->willThrowException(new \Exception('foo bar'));

        self::assertInstanceOf(
            YamlParser::class,
            $this->buildParser()
                ->parse(
                    'foo.bar',
                    $promise,
                    123
                )
        );
    }
}
