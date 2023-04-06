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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment;

use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

/**
 * Interface to define service able to take BuildableInterface instance and convert it / build them to OCI images and
 * push it to a registry.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface BuilderInterface
{
    public function configure(string $projectId, string $url, ?IdentityInterface $auth): BuilderInterface;

    /**
     * @param PromiseInterface<string, mixed> $promise
     */
    public function buildImages(
        CompiledDeploymentInterface $compiledDeployment,
        string $workingPath,
        PromiseInterface $promise
    ): BuilderInterface;

    /**
     * @param PromiseInterface<string, mixed> $promise
     */
    public function buildVolumes(
        CompiledDeploymentInterface $compiledDeployment,
        string $workingPath,
        PromiseInterface $promise
    ): BuilderInterface;
}
