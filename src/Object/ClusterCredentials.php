<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Object;

use SensitiveParameter;
use Stringable;
use Teknoo\East\Foundation\Normalizer\Object\AutoTrait;
use Teknoo\East\Foundation\Normalizer\Object\ClassGroup;
use Teknoo\East\Foundation\Normalizer\Object\Normalize;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

/**
 * Immutable object storing data to perform authentication on a cluster manager.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[ClassGroup('default', 'api', 'crud')]
class ClusterCredentials implements
    IdentifiedObjectInterface,
    IdentityInterface,
    NormalizableInterface,
    TimestampableInterface,
    Stringable
{
    use ObjectTrait;
    use ImmutableTrait;
    use AutoTrait;

    #[Normalize(['default', 'api', 'crud'])]
    protected ?string $id = null;

    #[Normalize(['default', 'crud'], 'ca_certificate')]
    private string $caCertificate = '';

    #[Normalize(['default', 'crud'], 'client_certificate')]
    private string $clientCertificate = '';

    #[Normalize(['default', 'crud'], 'client_key')]
    private string $clientKey = '';

    #[Normalize(['default', 'crud'])]
    private string $token = '';

    #[Normalize(['default', 'api', 'crud'])]
    private string $username = '';

    #[Normalize(['default', 'crud'])]
    private string $password = '';

    public function __construct(
        string $caCertificate = '',
        string $clientCertificate = '',
        string $clientKey = '',
        string $token = '',
        string $username = '',
        #[SensitiveParameter]
        string $password = '',
        ?string $id = null,
    ) {
        $this->uniqueConstructorCheck();

        $this->caCertificate = $caCertificate;
        $this->clientCertificate = $clientCertificate;
        $this->clientKey = $clientKey;
        $this->token = $token;
        $this->username = $username;
        $this->password = $password;
        $this->id = $id;
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
}
