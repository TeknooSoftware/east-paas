<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes\Transcriber;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Teknoo\Kubernetes\Enums\RequestMethod;

use function array_filter;
use function array_slice;
use function array_values;
use function explode;
use function is_string;
use function json_decode;
use function rawurldecode;
use function trim;

/**
 * Minimal in-memory Kubernetes API, for one kind of objects, to use as `Client::sendRequest()` behind the real
 * repositories of the client: objects are listed with the label selectors and the field selectors on their name like
 * the Kubernetes API does, created, patched and deleted by their name.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class InMemoryKubernetesApi
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $objects = [];

    /**
     * @param array<string, mixed> $manifest
     */
    public function add(array $manifest): self
    {
        $this->objects[$manifest['metadata']['name']] = $manifest;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $name): ?array
    {
        return $this->objects[$name] ?? null;
    }

    /**
     * @param array<string, mixed> $object
     */
    private static function matches(array $object, string $selector, callable $value): bool
    {
        foreach (explode(',', $selector) as $requirement) {
            [$key, $expected] = explode('=', $requirement, 2) + [1 => null];
            if (null === $expected || $value($object, $key) !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, int|string|null> $query
     * @return array<string, mixed>
     */
    private function list(array $query): array
    {
        $items = array_filter(
            $this->objects,
            static fn (array $object): bool => (
                (
                    empty($query['labelSelector'])
                    || self::matches(
                        $object,
                        (string) $query['labelSelector'],
                        static fn (array $object, string $label): ?string => $object['metadata']['labels'][$label]
                            ?? null,
                    )
                )
                && (
                    empty($query['fieldSelector'])
                    || self::matches(
                        $object,
                        (string) $query['fieldSelector'],
                        static fn (array $object, string $field): ?string => match ($field) {
                            'metadata.name' => $object['metadata']['name'] ?? null,
                            'metadata.namespace' => $object['metadata']['namespace'] ?? null,
                            default => throw new RuntimeException("Field selector {$field} is not supported"),
                        },
                    )
                )
            ),
        );

        $items = array_values($items);
        if (isset($query['limit'])) {
            $items = array_slice($items, 0, (int) $query['limit']);
        }

        return ['items' => $items];
    }

    /**
     * @param array<string, int|string|null> $query
     * @return array<string, mixed>
     */
    public function __invoke(
        RequestMethod $method,
        string $uri,
        array $query = [],
        StreamInterface|string|array|null $body = null,
    ): array {
        $name = rawurldecode(explode('/', trim($uri, '/'))[1] ?? '');
        if (is_string($body)) {
            $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        switch ($method) {
            case RequestMethod::Get:
                return $this->list($query);
            case RequestMethod::Post:
            case RequestMethod::Put:
            case RequestMethod::Patch:
                $this->add($body);

                return $body;
            case RequestMethod::Delete:
                unset($this->objects[$name]);

                return [];
        }
    }
}
