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
use function restore_error_handler;
use function set_error_handler;
use function str_replace;

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
    private static string $defaultXsdUrl = 'http://xml.teknoo.software/schemas/east/paas-validation';

    /**
     * @var string[]
     */
    private static array $staticNodesNames = [
        'add',
        'aliases',
        'build-name',
        'builds',
        'capacity',
        'category',
        'command',
        'containers',
        'clusters',
        'defaults',
        'extends',
        'failure',
        'from',
        'from-map',
        'from-maps',
        'from-secret',
        'from-secrets',
        'fs-group',
        'healthcheck',
        'host',
        'http',
        'https-backend',
        'image',
        'images',
        'import-maps',
        'import-secrets',
        'ingresses',
        'initial-delay-seconds',
        'internal',
        'is-secure',
        'latest',
        'limit',
        'listen',
        'local-path',
        'maps',
        'max-unavailable-pods',
        'max-upgrading-pods',
        'meta',
        'mount-path',
        'name',
        'oci-registry-config-name',
        'options',
        'paas',
        'path',
        'path',
        'paths',
        'period-seconds',
        'persistent',
        'pod',
        'pods',
        'port',
        'ports',
        'probe',
        'protocol',
        'provider',
        'quotas',
        'replicas',
        'require',
        'requires',
        'reset-on-deployment',
        'resources',
        'row',
        'secret',
        'secrets',
        'security',
        'service',
        'services',
        'storage-provider',
        'storage-size',
        'strategy',
        'success',
        'tag',
        'target',
        'tcp',
        'threshold',
        'tls',
        'type',
        'upgrade',
        'variables',
        'version',
        'volumes',
        'writables',
    ];

    private string $xsdUrl;

    public function __construct(
        private readonly string $rootName,
        ?string $xsdUrl = null,
    ) {
        if (empty($xsdUrl)) {
            $this->xsdUrl = self::$defaultXsdUrl;
        } else {
            $this->xsdUrl = $xsdUrl;
        }
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
                $newNode = $document->createElementNS(
                    $this->xsdUrl,
                    $nodeName,
                    str_replace('%', 'pc', (string) $mixedElement)
                );
            } else {
                $newNode = $document->createElementNS($this->xsdUrl, $nodeName);
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
        $root = $document->createElementNS($this->xsdUrl, $this->rootName);
        $root->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            $this->xsdUrl . ' ' . $this->xsdUrl . '.xsd'
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
            if ($previousHandler) {
                restore_error_handler();
            }

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
