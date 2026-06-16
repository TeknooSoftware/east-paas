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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose;

use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;

use function array_values;
use function basename;
use function in_array;
use function is_array;
use function is_string;
use function str_starts_with;

/**
 * Mutable accumulator (builder) used by the Docker Compose driver's transcribers to collect the full Compose
 * Specification file, the Traefik dynamic configuration, the files to push to the host and the networks to
 * wire to Traefik, before the driver serializes them and runs the Ansible playbooks.
 *
 * It knows the Compose project name (the "namespace") and the dedicated, internal network name; Compose
 * prefixes the network with the project name on the host.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class Generation implements GenerationInterface
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $services = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $networks = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $volumes = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $configs = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $secrets = [];

    /**
     * @var array{
     *     http: array{routers: array<string, array<string, mixed>>, services: array<string, array<string, mixed>>},
     *     tcp: array{routers: array<string, array<string, mixed>>, services: array<string, array<string, mixed>>},
     *     udp: array{routers: array<string, array<string, mixed>>, services: array<string, array<string, mixed>>}
     * }
     */
    private array $traefik = [
        'http' => ['routers' => [], 'services' => []],
        'tcp' => ['routers' => [], 'services' => []],
        'udp' => ['routers' => [], 'services' => []],
    ];

    /**
     * @var array<int, array{certFile: string, keyFile: string}>
     */
    private array $certificates = [];

    private ?string $certResolver = null;

    /**
     * @var array<string, string>
     */
    private array $files = [];

    /**
     * @var array<int, string>
     */
    private array $networksToWire = [];

    public function __construct(
        private readonly string $projectName,
        private readonly string $dedicatedNetworkName = 'private',
    ) {
    }

    public function getProjectName(): string
    {
        return $this->projectName;
    }

    public function getDedicatedNetworkName(): string
    {
        return $this->dedicatedNetworkName;
    }

    public function addService(string $name, array $spec): GenerationInterface
    {
        $this->services[$name] = $spec;

        return $this;
    }

    public function addNetwork(string $name, array $spec): GenerationInterface
    {
        $this->networks[$name] = $spec;

        return $this;
    }

    public function addVolume(string $name, array $spec): GenerationInterface
    {
        $this->volumes[$name] = $spec;

        return $this;
    }

    public function addConfig(string $name, array $spec, ?string $content = null): GenerationInterface
    {
        $this->configs[$name] = $spec;

        if (null !== $content && isset($spec['file']) && is_string($spec['file'])) {
            $this->addFile($spec['file'], $content);
        }

        return $this;
    }

    public function addSecret(string $name, array $spec, ?string $content = null): GenerationInterface
    {
        $this->secrets[$name] = $spec;

        if (null !== $content && isset($spec['file']) && is_string($spec['file'])) {
            $this->addFile($spec['file'], $content);
        }

        return $this;
    }

    public function addTraefikRouter(string $kind, string $name, array $spec): GenerationInterface
    {
        $this->traefik[$kind]['routers'][$name] = $spec;

        return $this;
    }

    public function addTraefikService(string $kind, string $name, array $spec): GenerationInterface
    {
        $this->traefik[$kind]['services'][$name] = $spec;

        return $this;
    }

    public function addTlsCertificate(string $certFile, string $keyFile): GenerationInterface
    {
        $this->certificates[] = [
            'certFile' => $certFile,
            'keyFile' => $keyFile,
        ];

        return $this;
    }

    public function setCertResolver(string $name): GenerationInterface
    {
        $this->certResolver = $name;

        return $this;
    }

    public function addFile(string $relativePath, string $content): GenerationInterface
    {
        $this->files[$relativePath] = $content;

        return $this;
    }

    public function wireNetworkToTraefik(string $networkName): GenerationInterface
    {
        if (!in_array($networkName, $this->networksToWire, true)) {
            $this->networksToWire[] = $networkName;
        }

        return $this;
    }

    public function getComposeFile(): array
    {
        $compose = [];

        if (!empty($this->services)) {
            $compose['services'] = $this->services;
        }

        if (!empty($this->networks)) {
            $compose['networks'] = $this->networks;
        }

        if (!empty($this->volumes)) {
            $compose['volumes'] = $this->volumes;
        }

        if (!empty($this->configs)) {
            $compose['configs'] = $this->configs;
        }

        if (!empty($this->secrets)) {
            $compose['secrets'] = $this->secrets;
        }

        return $compose;
    }

    public function getTraefikConfig(): array
    {
        $config = [];

        foreach ($this->traefik as $kind => $sections) {
            $kindConfig = [];
            foreach ($sections as $section => $entries) {
                if (!empty($entries)) {
                    $kindConfig[$section] = $entries;
                }
            }

            if (!empty($kindConfig)) {
                $config[$kind] = $kindConfig;
            }
        }

        if (!empty($this->certificates)) {
            $tls = [];
            foreach ($this->certificates as $certificate) {
                $tls['certificates'][] = [
                    'certFile' => $certificate['certFile'],
                    'keyFile' => $certificate['keyFile'],
                ];
            }

            $config['tls'] = $tls;
        }

        if (null !== $this->certResolver) {
            $config['tls']['certResolver'] = $this->certResolver;
        }

        return $config;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFilesToCopy(): array
    {
        $entries = [];
        foreach ($this->files as $relativePath => $content) {
            $entries[] = [
                'src' => $relativePath,
                'dest' => $relativePath,
                'mode' => str_starts_with($relativePath, 'secrets/') ? '0600' : '0640',
            ];
        }

        return $entries;
    }

    public function getCertificatesToCopy(): array
    {
        $entries = [];
        foreach ($this->certificates as $certificate) {
            $entries[] = [
                'src' => $certificate['certFile'],
                'dest' => basename($certificate['certFile']),
            ];
            $entries[] = [
                'src' => $certificate['keyFile'],
                'dest' => basename($certificate['keyFile']),
            ];
        }

        return $entries;
    }

    public function getResetVolumes(): array
    {
        $names = [];
        foreach ($this->volumes as $name => $spec) {
            if (!empty($spec['x-paas-reset'])) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function getJobsToRun(): array
    {
        $names = [];
        foreach ($this->services as $name => $spec) {
            $profiles = $spec['profiles'] ?? [];
            if (is_array($profiles) && in_array('jobs', $profiles, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function getNetworksToWire(): array
    {
        return array_values($this->networksToWire);
    }
}
