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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ClusterCredentials implements
    ObjectInterface,
    IdentityInterface,
    NormalizableInterface,
    TimestampableInterface
{
    use ObjectTrait;
    use ImmutableTrait;

    private ?string $name = null;

    private ?string $serverCertificate = null;

    private ?string $privateKey = null;

    private ?string $publicKey = null;

    public function __construct(
        string $name = '',
        string $serverCertificate = '',
        string $privateKey = '',
        string $publicKey = ''
    ) {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->serverCertificate = $serverCertificate;
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function getServerCertificate(): string
    {
        return (string) $this->serverCertificate;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getPrivateKey(): string
    {
        return (string) $this->privateKey;
    }

    public function getPublicKey(): string
    {
        return (string) $this->publicKey;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'name' => $this->getId(),
            'server_certificate' => $this->getServerCertificate(),
            'private_key' => $this->getPrivateKey(),
            'public_key' => $this->getPublicKey(),
        ]);

        return $this;
    }
}
