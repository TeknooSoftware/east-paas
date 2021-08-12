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

namespace Teknoo\East\Paas\Parser;

use DOMDocument;
use DOMElement;
use RuntimeException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_flip;
use function is_array;
use function is_string;
use function libxml_get_last_error;

/**
 * Object able to validate a Yaml structure. As there is no standardized validation system in yaml,
 * but it is included in the XML Standard, this validator will convert the decoded Yaml array to a Xml stream
 * thanks to DOMDocument and validate it with the xsd `src/Contracts/Configuration/paas_validation.xsd`.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class YamlValidator
{
    private static string $xsdUrl = 'http://xml.teknoo.it/schemas/east/paas-validation';

    /**
     * @var string[]
     */
    private static array $staticNodesNames = [
        'paas',
        'secrets',
        'volumes',
        'images',
        'builds',
        'pods',
        'services',
        'ingresses',
        'version',
        'namespace',
        'build-name',
        'tag',
        'variables',
        'path',
        'host',
        'tls',
        'provider',
        'secret',
        'service',
        'name',
        'port',
        'paths',
        'containers',
        'mount-path',
        'local-path',
        'from',
        'persistent',
        'from-secret',
        'from-secrets',
        'storage-provider',
        'add',
        'image',
        'latest',
        'listen',
        'replicas',
        'options',
        'ports',
        'target',
        'pod',
        'protocol',
        'internal',
        'row',
    ];

    public function __construct(
        private string $rootName
    ) {
    }

    /**
     * @param array<mixed, mixed> $values
     */
    private function parse(
        array &$values,
        DOMDocument $document,
        DOMElement $parent,
        bool $isPod = false,
        bool $isVolume = false
    ): void {
        foreach ($values as $index => $mixedElement) {
            $name = $index;
            if (!is_string($name)) {
                $name = 'row';
            }

            $isStatic = isset(array_flip(static::$staticNodesNames)[$name]);
            $nodeName = $name;
            if (false === $isStatic) {
                $nodeName = 'node';

                if (true === $isVolume && isset($values[$name]['add'])) {
                    $nodeName = 'embedded-' . $nodeName;
                } elseif (true === $isVolume && isset($values[$name]['from'])) {
                    $nodeName = 'from-' . $nodeName;
                } elseif (true === $isVolume && isset($values[$name]['persistent'])) {
                    $nodeName = 'persistent-' . $nodeName;
                } elseif (true === $isVolume && isset($values[$name]['from-secret'])) {
                    $nodeName = 'secret-' . $nodeName;
                }
            }

            if (!is_array($mixedElement)) {
                $newNode = $document->createElementNS(static::$xsdUrl, $nodeName, (string) $mixedElement);
            } else {
                $newNode = $document->createElementNS(static::$xsdUrl, $nodeName);
                $this->parse(
                    $mixedElement,
                    $document,
                    $newNode,
                    $isPod || 'pods' === $name,
                    $isPod && 'volumes' === $name
                );
            }

            if (false === $isStatic) {
                $newNode->setAttribute('name', $name);
            }

            $parent->appendChild($newNode);
        }
    }

    /**
     * @param array<mixed, mixed> $values
     */
    private function convert(array $values): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->createElementNS(static::$xsdUrl, $this->rootName);
        $root->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            static::$xsdUrl . ' ' . static::$xsdUrl . '.xsd'
        );

        $document->appendChild($root);

        $this->parse($values, $document, $root);

        return $document;
    }

    /**
     * @param array<mixed, mixed> $values
     */
    public function validate(array $values, string $schema, PromiseInterface $promise): self
    {
        $xmlError = null;
        try {
            $document = $this->convert($values);

            $document->schemaValidateSource($schema);
        } catch (Throwable $error) {
            $xmlError = $error;
        } finally {
            $libError = libxml_get_last_error();

            if ($xmlError || $libError) {
                $exception = new RuntimeException((string) ($xmlError ?? $libError->message));
                $promise->fail($exception);

                return $this;
            }

            $promise->success($values);
        }

        return $this;
    }
}
