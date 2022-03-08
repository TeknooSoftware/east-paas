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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
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
 * Immutable object storing value to perform a HTTP authentication on a image registry
 *
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

    public function __construct(
        private readonly string $username = '',
        private readonly string $password = '',
        private readonly string $email = '',
        private readonly string $auth = '',
        private readonly string $serverAddress = ''
    ) {
        $this->uniqueConstructorCheck();
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
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAuth(): string
    {
        return $this->auth;
    }

    public function getServerAddress(): string
    {
        return $this->serverAddress;
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
