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

namespace Teknoo\East\Paas\Contracts\Cluster;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * Contrat defining a client driver able to perform a deployment and expose services on a cluster from a
 * CompiledDeploymentInterface instance.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface DriverInterface
{
    public function configure(
        string $url,
        ?IdentityInterface $identity,
        DefaultsBag $defaultsBag,
        string $namespace,
        bool $useHierarchicalNamespaces,
    ): DriverInterface;

    /**
     * @param PromiseInterface<array<string, mixed>, mixed> $promise
     */
    public function deploy(CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise): DriverInterface;

    /**
     * @param PromiseInterface<array<string, mixed>, mixed> $promise
     */
    public function expose(CompiledDeploymentInterface $compiledDeployment, PromiseInterface $promise): DriverInterface;
}
