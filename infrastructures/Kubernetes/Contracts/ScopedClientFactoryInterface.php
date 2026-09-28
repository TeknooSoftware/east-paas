<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts;

use SensitiveParameter;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\RepositoryRegistry;

/**
 * Interface defining a factory able to lend a `Kubernetes Client` instance for the time of a callback, and to
 * remove, once the callback has returned or thrown, all resources materialized for this client (like the temporary
 * files holding the cluster's credentials) instead of keeping them as long as the factory, a shared service living
 * as long as the worker. The client must not be used outside of the callback.
 *
 * The Kubernetes driver uses this method, when it is available, to get a new client for each stage.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface ScopedClientFactoryInterface extends ClientFactoryInterface
{
    /**
     * @param callable(Client): mixed $callback
     */
    public function withClient(
        string $master,
        #[SensitiveParameter] ?ClusterCredentials $credentials,
        callable $callback,
        ?RepositoryRegistry $repositoryRegistry = null,
    ): ScopedClientFactoryInterface;
}
