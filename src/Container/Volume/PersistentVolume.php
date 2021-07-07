<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Container\Volume;

use Teknoo\East\Paas\Contracts\Container\PersistentVolumeInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable value object, representing a normalized configuration about PersistentVolume, where store persistent data,
 * to mount into pods's filesystem, They are not impacted by deployment, and uncorrelated with any pods lifecycle.
 * Data stay available when after pod deletion.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class PersistentVolume implements ImmutableInterface, PersistentVolumeInterface
{
    use ImmutableTrait;

    private string $name;

    private string $mountPath;

    private ?string $storageIdentifier = null;

    public function __construct(
        string $name,
        string $mountPath,
        string $storageIdentifier = null
    ) {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->mountPath = $mountPath;
        $this->storageIdentifier = $storageIdentifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMountPath(): string
    {
        return $this->mountPath;
    }

    public function getStorageIdentifier(): ?string
    {
        return $this->storageIdentifier;
    }
}
