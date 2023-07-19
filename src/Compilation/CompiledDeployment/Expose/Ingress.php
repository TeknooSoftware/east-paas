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

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Expose;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized configuration about Ingress in a deployment to expose some pod via
 * an ingress service (only for HTTP/S)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Ingress implements ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @param array<integer, IngressPath> $paths
     * @param array<string, string|int|bool|array<string, string>> $meta
     */
    public function __construct(
        private readonly string $name,
        private readonly string $host,
        private readonly ?string $provider,
        private readonly ?string $defaultServiceName,
        private readonly ?int $defaultServicePort,
        private readonly array $paths,
        private readonly ?string $tlsSecret,
        private readonly bool $httpsBackend,
        private readonly array $meta = [],
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function getDefaultServiceName(): ?string
    {
        return $this->defaultServiceName;
    }

    public function getDefaultServicePort(): ?int
    {
        return $this->defaultServicePort;
    }

    /**
     * @return array<integer, IngressPath>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    public function getTlsSecret(): ?string
    {
        return $this->tlsSecret;
    }

    public function isHttpsBackend(): bool
    {
        return $this->httpsBackend;
    }

    /**
     * @return array<string, string|int|bool|array<string, string>>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }
}
