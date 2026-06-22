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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts;

use SensitiveParameter;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * Contract for the component running an Ansible playbook (via the raw `ansible-playbook` binary through
 * Symfony Process) to deploy or expose the generated Compose Specification + Traefik dynamic configuration
 * on the remote Docker host over SSH.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface RunnerInterface
{
    /**
     * @param array<string, mixed> $extraVars
     * @param PromiseInterface<array<string, mixed>|string, mixed> $promise
     */
    public function run(
        string $playbookPath,
        string $inventoryPath,
        array $extraVars,
        #[SensitiveParameter] ?ClusterCredentials $credentials,
        PromiseInterface $promise,
    ): RunnerInterface;
}
