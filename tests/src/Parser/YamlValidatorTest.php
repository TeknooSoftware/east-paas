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

namespace Teknoo\Tests\East\Paas\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Configuration\YamlParser;
use Teknoo\East\Paas\Parser\YamlValidator;

/**
 * @author      Richard Déloge <richard@teknoo.software>
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(YamlValidator::class)]
class YamlValidatorTest extends TestCase
{
    private function buildValidator(): YamlValidator
    {
        return new YamlValidator('root');
    }

    private function getYamlArray(): array
    {
        $fileName = dirname(__DIR__, 2) . '/fixtures/basic_full.paas.yaml';
        $conf = file_get_contents($fileName);
        return (new Parser())->parse($conf);
    }

    private function getXsdFile(): string
    {
        $fileName = \dirname(__DIR__, 3) . '/src/Contracts/Compilation/paas_validation.xsd';

        return \file_get_contents($fileName);
    }

    public function testValidConf()
    {
        $configuration = $this->getYamlArray();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success')->with($configuration);
        $promise->expects($this->never())->method('fail');

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
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

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
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

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
