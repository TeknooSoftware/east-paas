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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\Compiler;

use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * Compiler to fill the DefaultsBag instance from default value defined as env var for the runner,
 * or from the JobUnit's configuration / paas yaml.
 * (Migrated from the conductor to become a dedicated compiler)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DefaultsCompiler implements CompilerInterface
{
    private const CONFIG_KEY_STORAGE_PROVIDER = 'storage-provider';
    private const CONFIG_KEY_STORAGE_SIZE = 'storage-size';
    private const CONFIG_KEY_OCI_REGISTRY_CONFIG_NAME = 'oci-registry-config-name';
    private const CONFIG_KEY_CLUSTERS = 'clusters';

    public function __construct(
        private readonly ?string $storageIdentifier,
        private readonly ?string $storageSize,
        private readonly ?string $defaultOciRegistryConfig,
    ) {
    }

    private function initBag(DefaultsBag $bag): void
    {
        if (!empty($this->storageIdentifier)) {
            $bag->set(self::CONFIG_KEY_STORAGE_PROVIDER, $this->storageIdentifier);
        }

        if (!empty($this->storageSize)) {
            $bag->set(self::CONFIG_KEY_STORAGE_SIZE, $this->storageSize);
        }

        $bag->set(self::CONFIG_KEY_OCI_REGISTRY_CONFIG_NAME, $this->defaultOciRegistryConfig);
    }

    /**
     * @param array<string, mixed> $definitions
     */
    private function updateBag(DefaultsBag $defaultsBag, array &$definitions): void
    {
        $keys = [
            self::CONFIG_KEY_STORAGE_PROVIDER,
            self::CONFIG_KEY_STORAGE_SIZE,
            self::CONFIG_KEY_OCI_REGISTRY_CONFIG_NAME,
        ];

        foreach ($keys as $key) {
            if (!empty($definitions[$key])) {
                $defaultsBag->set($key, $definitions[$key]);
            }
        }
    }

    public function compile(
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): CompilerInterface {
        $this->initBag($defaultsBag);

        $this->updateBag($defaultsBag, $definitions);

        if (!empty($definitions[self::CONFIG_KEY_CLUSTERS])) {
            foreach ($definitions[self::CONFIG_KEY_CLUSTERS] as $name => &$defs) {
                $this->updateBag(
                    $defaultsBag->forCluster($name),
                    $defs,
                );
            }
        }

        return $this;
    }
}
