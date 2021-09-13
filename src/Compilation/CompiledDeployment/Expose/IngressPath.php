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

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Expose;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized path in Ingress Configuration in a deployment to expose some pod
 * via an ingress service (only for HTTP/S)
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class IngressPath implements ImmutableInterface
{
    use ImmutableTrait;

    private string $path;

    private string $serviceName;

    private int $servicePort;

    public function __construct(string $path, string $serviceName, int $servicePort)
    {
        $this->uniqueConstructorCheck();

        $this->path = $path;
        $this->serviceName = $serviceName;
        $this->servicePort = $servicePort;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getServicePort(): int
    {
        return $this->servicePort;
    }
}
