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

/**
 * Contract for the mutable accumulator used by the Docker Compose driver's transcribers to collect the
 * full Compose Specification file, the Traefik dynamic configuration, the files to push to the host and
 * the networks to wire to Traefik, before the driver serializes them and runs the Ansible playbooks.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface GenerationInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getComposeFile(): array;

    /**
     * @return array<string, mixed>
     */
    public function getTraefikConfig(): array;

    /**
     * @return array<string, string>
     */
    public function getFiles(): array;

    /**
     * @return array<int, string>
     */
    public function getNetworksToWire(): array;
}
