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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\SerializingConfiguration;

use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser
 */
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
        $promise->expects(self::once())->method('success')->with(['foo' => 'bar']);
        $promise->expects(self::never())->method('fail');

        $this->getParserMock()
            ->expects(self::any())
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
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $this->getParserMock()
            ->expects(self::any())
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
