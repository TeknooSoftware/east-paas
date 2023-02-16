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

use Stringable;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

/**
 * Immutable object storing data to perform authentication on a cluster manager.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ClusterCredentials implements
    IdentifiedObjectInterface,
    IdentityInterface,
    NormalizableInterface,
    TimestampableInterface,
    Stringable
{
    use ObjectTrait;
    use ImmutableTrait;

    private string $caCertificate = '';

    private string $clientCertificate = '';

    private string $clientKey = '';

    private string $token = '';

    private string $username = '';

    private string $password = '';

    public function __construct(
        string $caCertificate = '',
        string $clientCertificate = '',
        string $clientKey = '',
        string $token = '',
        string $username = '',
        string $password = '',
    ) {
        $this->uniqueConstructorCheck();

        $this->caCertificate = $caCertificate;
        $this->clientCertificate = $clientCertificate;
        $this->clientKey = $clientKey;
        $this->token = $token;
        $this->username = $username;
        $this->password = $password;
    }

    public function getName(): string
    {
        return $this->username;
    }

    public function getCaCertificate(): string
    {
        return $this->caCertificate;
    }

    public function getClientCertificate(): string
    {
        return $this->clientCertificate;
    }

    public function getClientKey(): string
    {
        return $this->clientKey;
    }

    public function __toString(): string
    {
        return $this->username;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
            '@class' => self::class,
            'id' => $this->getId(),
            'ca_certificate' => $this->getCaCertificate(),
            'client_certificate' => $this->getClientCertificate(),
            'client_key' => $this->getClientKey(),
            'token' => $this->getToken(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
        ]);

        return $this;
    }
}
