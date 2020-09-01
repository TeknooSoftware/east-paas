<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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
