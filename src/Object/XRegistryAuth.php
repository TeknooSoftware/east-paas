<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Object;

use SensitiveParameter;
use Stringable;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\GroupsTrait;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityWithConfigNameInterface;
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Immutable object storing value to perform a HTTP authentication on a image registry
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class XRegistryAuth implements
    IdentifiedObjectInterface,
    NormalizableInterface,
    IdentityWithConfigNameInterface,
    TimestampableInterface,
    Stringable
{
    use ObjectTrait;
    use ImmutableTrait;
    use GroupsTrait;
    use ExportConfigurationsTrait;

    private string $username = '';

    private string $password = '';

    private string $email = '';

    private string $auth = '';

    private string $serverAddress = '';

    /**
     * @var array<string, string[]>
     */
    private static array $exportConfigurations = [
        '@class' => ['all', 'api'],
        'id' => ['all', 'api'],
        'username' => ['all', 'api'],
        'password' => ['all'],
        'email' => ['all', 'api'],
        'auth' => ['all'],
        'server_address' => ['all', 'api'],
    ];

    public function __construct(
        string $username = '',
        #[SensitiveParameter]
        string $password = '',
        string $email = '',
        string $auth = '',
        string $serverAddress = '',
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

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $data = [
            '@class' => self::class,
            'id' => $this->getId(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'email' => $this->getEmail(),
            'auth' => $this->getAuth(),
            'server_address' => $this->getServerAddress(),
        ];

        $this->setGroupsConfiguration(self::$exportConfigurations);

        $normalizer->injectData(
            $this->filterExport(
                $data,
                (array) ($context['groups'] ?? ['all']),
            )
        );

        return $this;
    }
}
