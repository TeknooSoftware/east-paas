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

class XRegistryAuth implements
    ObjectInterface,
    NormalizableInterface,
    IdentityInterface,
    TimestampableInterface
{
    use ObjectTrait;
    use ImmutableTrait;

    private ?string $username = null;

    private ?string $password = null;

    private ?string $email = null;

    private ?string $auth = null;

    private ?string $serverAddress = null;

    public function __construct(
        string $username = '',
        string $password = '',
        string $email = '',
        string $auth = '',
        string $serverAddress = ''
    ) {
        $this->uniqueConstructorCheck();

        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->auth = $auth;
        $this->serverAddress = $serverAddress;
    }

    public function getName(): string
    {
        return $this->getUsername();
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function getEmail(): string
    {
        return (string) $this->email;
    }

    public function getAuth(): string
    {
        return (string) $this->auth;
    }

    public function getServerAddress(): string
    {
        return (string) $this->serverAddress;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'email' => $this->getEmail(),
            'auth' => $this->getAuth(),
            'server_address' => $this->getServerAddress(),
        ]);

        return $this;
    }
}
