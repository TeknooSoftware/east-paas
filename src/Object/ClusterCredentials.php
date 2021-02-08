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

    private ?string $serverCertificate = null;

    private ?string $token = null;

    private ?string $username = null;

    private ?string $password = null;

    public function __construct(
        string $serverCertificate = '',
        string $token = '',
        string $username = '',
        string $password = ''
    ) {
        $this->uniqueConstructorCheck();

        $this->serverCertificate = $serverCertificate;
        $this->token = $token;
        $this->username = $username;
        $this->password = $password;
    }

    public function getName(): string
    {
        return (string) $this->username;
    }

    public function getServerCertificate(): string
    {
        return (string) $this->serverCertificate;
    }

    public function __toString(): string
    {
        return (string) $this->username;
    }

    public function getToken(): string
    {
        return (string) $this->token;
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'server_certificate' => $this->getServerCertificate(),
            'token' => $this->getToken(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
        ]);

        return $this;
    }
}
