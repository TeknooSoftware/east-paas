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
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityWithConfigNameInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable object storing value to perform a HTTP authentication on a image registry
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[ClassGroup('default', 'api', 'crud')]
class XRegistryAuth implements
    IdentifiedObjectInterface,
    NormalizableInterface,
    IdentityWithConfigNameInterface,
    TimestampableInterface,
    Stringable
{
    use ObjectTrait;
    use ImmutableTrait;
    use AutoTrait;

    #[Normalize(['default', 'api', 'crud'])]
    protected ?string $id = null;

    #[Normalize(['default', 'api', 'crud'])]
    private string $username = '';

    #[Normalize(['default', 'crud'])]
    private string $password = '';

    #[Normalize(['default', 'api', 'crud'])]
    private string $email = '';

    #[Normalize(['default', 'crud'])]
    private string $auth = '';

    #[Normalize(['default', 'api', 'crud'], 'server_address')]
    private string $serverAddress = '';

    public function __construct(
        string $username = '',
        #[SensitiveParameter]
        string $password = '',
        string $email = '',
        string $auth = '',
        string $serverAddress = '',
        ?string $id = null,
    ) {
        $this->uniqueConstructorCheck();

        $this->username = $username;
        $this->password = $password;
        $this->email = $email;
        $this->auth = $auth;
        $this->serverAddress = $serverAddress;
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->getUsername();
    }

    public function __toString(): string
    {
        return $this->getName();
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

    public function getConfigName(): string
    {
        return $this->getAuth();
    }

    public function getAuth(): string
    {
        return $this->auth;
    }

    public function getServerAddress(): string
    {
        return $this->serverAddress;
    }
}
