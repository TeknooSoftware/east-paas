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
