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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Parser;

use DOMDocument;
use DOMElement;
use RuntimeException;
use Teknoo\East\Paas\Parser\Exception\ValidationException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function array_flip;
use function is_array;
use function is_string;
use function is_scalar;
use function libxml_clear_errors;
use function libxml_get_last_error;
use function set_error_handler;

use const E_WARNING;

/**
 * Object able to validate a Yaml structure. As there is no standardized validation system in yaml,
 * but it is included in the XML Standard, this validator will convert the decoded Yaml array to a Xml stream
 * thanks to DOMDocument and validate it with the xsd `src/Contracts/Configuration/paas_validation.xsd`.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class YamlValidator
{
    private static string $xsdUrl = 'http://xml.teknoo.it/schemas/east/paas-validation';

    /**
     * @var string[]
     */
    private static array $staticNodesNames = [
        'paas',
        'defaults',
        'maps',
        'secrets',
        'volumes',
        'images',
        'builds',
        'pods',
        'services',
        'ingresses',
        'version',
        'namespace',
        'hierarchical-namespaces',
        'build-name',
        'tag',
        'variables',
        'path',
        'host',
        'tls',
        'https-backend',
        'provider',
        'secret',
        'service',
        'name',
        'port',
        'paths',
        'requires',
        'containers',
        'oci-registry-config-name',
        'healthcheck',
        'initial-delay-seconds',
        'period-seconds',
        'probe',
        'command',
        'http',
        'tcp',
        'path',
        'is-secure',
        'threshold',
        'failure',
        'success',
        'upgrade',
        'security',
        'fs-group',
        'strategy',
        'max-upgrading-pods',
        'max-unavailable-pods',
        'mount-path',
        'local-path',
        'from',
        'persistent',
        'reset-on-deployment',
        'from-secret',
        'from-secrets',
        'import-secrets',
        'from-map',
        'from-maps',
        'import-maps',
        'storage-provider',
        'storage-size',
        'add',
        'writables',
        'image',
        'extends',
        'latest',
        'listen',
        'replicas',
        'type',
        'options',
        'ports',
        'target',
        'pod',
        'protocol',
        'internal',
        'row',
    ];

    public function __construct(
        private readonly string $rootName
    ) {
    }

    /**
     * @param array<string|int, mixed> $values
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

            $isStatic = isset(array_flip(self::$staticNodesNames)[$name]);
            $nodeName = $name;
            if (false === $isStatic) {
                $nodeName = 'node';

                if (true === $isVolume && is_array($values[$name]) && isset($values[$name]['add'])) {
                    $nodeName = 'embedded-' . $nodeName;
                } elseif (true === $isVolume && is_array($values[$name]) && isset($values[$name]['from'])) {
                    $nodeName = 'from-' . $nodeName;
                } elseif (true === $isVolume && is_array($values[$name]) && isset($values[$name]['persistent'])) {
                    $nodeName = 'persistent-' . $nodeName;
                } elseif (true === $isVolume && is_array($values[$name]) && isset($values[$name]['from-map'])) {
                    $nodeName = 'map-' . $nodeName;
                } elseif (true === $isVolume && is_array($values[$name]) && isset($values[$name]['from-secret'])) {
                    $nodeName = 'secret-' . $nodeName;
                }
            }

            if (!is_array($mixedElement) && is_scalar($mixedElement)) {
                $newNode = $document->createElementNS(self::$xsdUrl, $nodeName, (string) $mixedElement);
            } else {
                $newNode = $document->createElementNS(self::$xsdUrl, $nodeName);
                $this->parse(
                    $mixedElement,
                    $document,
                    $newNode,
                    $isPod || 'pods' === $name,
                    $isPod && 'volumes' === $name
                );
            }

            if (!$isStatic) {
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
        $root = $document->createElementNS(self::$xsdUrl, $this->rootName);
        $root->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            self::$xsdUrl . ' ' . self::$xsdUrl . '.xsd'
        );

        $document->appendChild($root);

        $this->parse($values, $document, $root);

        return $document;
    }

    /**
     * @param array<mixed, mixed> $values
     * @param PromiseInterface<array<int|string, mixed>, mixed> $promise
     */
    public function validate(array $values, string $schema, PromiseInterface $promise): self
    {
        $xmlError = null;
        $previousHandler = set_error_handler(
            fn (int $errno, string $errstr) => throw new ValidationException($errstr, $errno),
            E_WARNING
        );

        try {
            $document = $this->convert($values);

            $document->schemaValidateSource($schema);
        } catch (Throwable $error) {
            $xmlError = $error;
        } finally {
            set_error_handler($previousHandler);

            $libError = libxml_get_last_error();
            libxml_clear_errors();

            if ($xmlError || false !== $libError) {
                $exception = new RuntimeException((string) ($xmlError ?? $libError->message));
                $promise->fail($exception);

                return $this;
            }

            $promise->success($values);
        }

        return $this;
    }
}
