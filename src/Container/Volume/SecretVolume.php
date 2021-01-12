<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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
use Teknoo\East\Paas\Contracts\Container\VolumeInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SecretVolume implements ImmutableInterface, VolumeInterface
{
    use ImmutableTrait;

    private string $name;

    private string $mountPath;

    private string $secretIdentifier;

    public function __construct(
        string $name,
        string $mountPath,
        string $secretIdentifier
    ) {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->mountPath = $mountPath;
        $this->secretIdentifier = $secretIdentifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMountPath(): string
    {
        return $this->mountPath;
    }

    public function getSecretIdentifier(): string
    {
        return $this->secretIdentifier;
    }
}