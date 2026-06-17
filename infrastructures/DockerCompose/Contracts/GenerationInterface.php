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

use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\FileToCopy;

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
     * Compose project name (the "namespace"), used by `docker compose -p <project>`.
     */
    public function getProjectName(): string;

    /**
     * Name of the dedicated, internal network declared in the Compose file (Compose prefixes it with the
     * project name on the host).
     */
    public function getDedicatedNetworkName(): string;

    /**
     * @param array<string, mixed> $spec
     */
    public function addService(string $name, array $spec): GenerationInterface;

    /**
     * @param array<string, mixed> $spec
     */
    public function addNetwork(string $name, array $spec): GenerationInterface;

    /**
     * @param array<string, mixed> $spec
     */
    public function addVolume(string $name, array $spec): GenerationInterface;

    /**
     * @param array<string, mixed> $spec
     */
    public function addConfig(string $name, array $spec, ?string $content = null): GenerationInterface;

    /**
     * @param array<string, mixed> $spec
     */
    public function addSecret(string $name, array $spec, ?string $content = null): GenerationInterface;

    /**
     * @param 'http'|'tcp'|'udp' $kind
     * @param array<string, mixed> $spec
     */
    public function addTraefikRouter(string $kind, string $name, array $spec): GenerationInterface;

    /**
     * @param 'http'|'tcp'|'udp' $kind
     * @param array<string, mixed> $spec
     */
    public function addTraefikService(string $kind, string $name, array $spec): GenerationInterface;

    public function addTlsCertificate(string $certFile, string $keyFile): GenerationInterface;

    public function setCertResolver(string $name): GenerationInterface;

    public function addFile(string $relativePath, string $content): GenerationInterface;

    public function wireNetworkToTraefik(string $networkName): GenerationInterface;

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
     * Pushed files described for the deploy/expose playbook `copy` loops as a list of
     * `{src, dest, mode}` entries (relative paths kept identical on the host below the project dir).
     *
     * @return array<int, FileToCopy>
     */
    public function getFilesToCopy(): array;

    /**
     * TLS cert/key files described for the expose playbook `copy` loop as a list of `{src, dest}`
     * entries; `dest` is the bare filename dropped into Traefik's certs directory.
     *
     * @return array<int, FileToCopy>
     */
    public function getCertificatesToCopy(): array;

    /**
     * Names of the project volumes flagged `resetOnDeployment` (the `x-paas-reset` marker), to be removed
     * by the deploy playbook before the stack is brought up. Names are unprefixed by the project.
     *
     * @return array<int, string>
     */
    public function getResetVolumes(): array;

    /**
     * Names of the Compose services guarding during-deployment jobs (the `jobs` profile / `x-paas-job`
     * marker), to be run once by the deploy playbook with `docker compose --profile jobs run --rm <svc>`.
     *
     * @return array<int, string>
     */
    public function getJobsToRun(): array;

    /**
     * @return array<int, string>
     */
    public function getNetworksToWire(): array;
}
