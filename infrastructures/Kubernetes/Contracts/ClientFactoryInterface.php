<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts;

use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\RepositoryRegistry;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * Interface defining a factory in the DI to create, on demand, a new `Kubernetes Client` instance,
 * needed to execute manifest on the remote Kubernetes manager.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface ClientFactoryInterface
{
    public function __invoke(
        string $master,
        ?ClusterCredentials $credentials,
        ?RepositoryRegistry $repositoryRegistry = null
    ): Client;
}
