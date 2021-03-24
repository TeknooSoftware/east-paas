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

namespace Teknoo\East\Paas\Container\Expose;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Ingress implements ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @param array<integer, IngressPath> $paths
     */
    public function __construct(
        private string $name,
        private string $host,
        private ?string $provider,
        private ?string $defaultServiceName,
        private ?int $defaultServicePort,
        private array $paths,
        private ?string $tlsSecret
    ) {
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
}
