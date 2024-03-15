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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment\Volume;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\Reference;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\PersistentVolumeInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized configuration about PersistentVolume, where store persistent data,
 * to mount into pods's filesystem, They are not impacted by deployment, and uncorrelated with any pods lifecycle.
 * Data stay available when after pod deletion.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class PersistentVolume implements ImmutableInterface, PersistentVolumeInterface
{
    use ImmutableTrait;

    public function __construct(
        private readonly string $name,
        private readonly string $mountPath,
        private readonly string|Reference|null $storageIdentifier = null,
        private readonly string|Reference|null $storageSize = null,
        private readonly bool $resetOnDeployment = false,
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMountPath(): string
    {
        return $this->mountPath;
    }

    public function getStorageIdentifier(): string|Reference|null
    {
        return $this->storageIdentifier;
    }

    public function getStorageSize(): string|Reference|null
    {
        return $this->storageSize;
    }

    public function isResetOnDeployment(): bool
    {
        return $this->resetOnDeployment;
    }
}
