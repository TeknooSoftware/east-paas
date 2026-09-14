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
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\InlineContent;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\MountedFile;

/**
 * Contract for the mutable accumulator used by the Docker Compose driver's transcribers to collect the
 * full Compose Specification file, the Traefik dynamic configuration, the files to push to the host and
 * the per-ingress TLS certificates, before the driver serializes them and runs the Ansible playbooks.
 *
 * Services reach each other on a per-project, dedicated Docker network whose full name
 * (`<project>-<network>`) is exposed by getNetworkName() and pinned in the Compose file with an explicit
 * `name:`. The deploy playbook connects Traefik to that same name returned by getNetworkName().
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface AccumulatorInterface
{
    /**
     * Compose project name (the "namespace"), used by `docker compose -p <project>`.
     */
    public function getProjectName(): string;

    /**
     * Full, project-qualified name of the dedicated network (`<project>-<network>`), used both as the
     * Compose network key/`name:` and as the name every service attaches to.
     */
    public function getNetworkName(): string;

    /**
     * @param array<string, mixed> $spec
     */
    public function addService(string $name, array $spec): AccumulatorInterface;

    /**
     * Add `ports` host-publishing entries to an already declared service (public services).
     *
     * @param array<int, string> $ports
     */
    public function publishPorts(string $name, array $ports): AccumulatorInterface;

    /**
     * Declare an extra DNS alias, on the dedicated network, for an already declared service (the PaaS
     * service names pointing to a pod). Ignored when the service is unknown or shares another service's
     * network namespace.
     */
    public function addNetworkAlias(string $name, string $alias): AccumulatorInterface;

    /**
     * @param array<string, mixed> $spec
     */
    public function addVolume(string $name, array $spec): AccumulatorInterface;

    public function addConfig(string $name, MountedFile|InlineContent $definition): AccumulatorInterface;

    public function addSecret(string $name, MountedFile|InlineContent $definition): AccumulatorInterface;

    /**
     * @param 'http'|'tcp'|'udp' $kind
     * @param array<string, mixed> $spec
     */
    public function addTraefikRouter(string $kind, string $name, array $spec): AccumulatorInterface;

    /**
     * @param 'http'|'tcp'|'udp' $kind
     * @param array<string, mixed> $spec
     */
    public function addTraefikService(string $kind, string $name, array $spec): AccumulatorInterface;

    /**
     * Declare a Traefik HTTP `serversTransport` (e.g. to skip the TLS verification of an HTTPS backend).
     *
     * @param array<string, mixed> $spec
     */
    public function addTraefikServersTransport(string $name, array $spec): AccumulatorInterface;

    /**
     * Register a TLS cert/key pair (paths relative to the working directory, as added with addFile()). The
     * files are pushed under their base name into the Traefik certificates directory and referenced from
     * the dynamic configuration by their path as seen by Traefik.
     */
    public function addTlsCertificate(string $certFile, string $keyFile): AccumulatorInterface;

    public function setCertResolver(string $name): AccumulatorInterface;

    public function addFile(string $relativePath, string $content): AccumulatorInterface;

    /**
     * Record a non-fatal warning (a PaaS feature only partially supported on a Docker Compose host); the
     * driver reports them in the stage result stored in the job History.
     */
    public function addWarning(string $message): AccumulatorInterface;

    /**
     * @return array<int, string>
     */
    public function getWarnings(): array;

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
     * Runs of the Compose services guarding during-deployment jobs (the `jobs` profile), to be executed
     * sequentially by the deploy playbook with `docker compose --profile jobs run --rm <svc>`: one entry per
     * completion, with the time limit (seconds, 0 = none) and the accepted exit codes.
     *
     * @return array<int, array{service: string, run: int, completions: int, timeout: int, ok_codes: array<int, int>}>
     */
    public function getJobsToRun(): array;
}
