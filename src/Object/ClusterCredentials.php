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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Object;

use SensitiveParameter;
use Stringable;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\GroupsTrait;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

/**
 * Immutable object storing data to perform authentication on a cluster manager.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
    use GroupsTrait;
    use ExportConfigurationsTrait;

    private string $caCertificate = '';

    private string $clientCertificate = '';

    private string $clientKey = '';

    private string $token = '';

    private string $username = '';

    private string $password = '';

    /**
     * @var array<string, string[]>
     */
    private static array $exportConfigurations = [
        '@class' => ['default', 'api', 'crud'],
        'id' => ['default', 'api', 'crud'],
        'ca_certificate' => ['default', 'crud'],
        'client_certificate' => ['default', 'crud'],
        'client_key' => ['default', 'crud'],
        'token' => ['default', 'crud'],
        'username' => ['default', 'api', 'crud'],
        'password' => ['default', 'crud'],
    ];

    public function __construct(
        string $caCertificate = '',
        string $clientCertificate = '',
        string $clientKey = '',
        string $token = '',
        string $username = '',
        #[SensitiveParameter]
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
        $data = [
            '@class' => self::class,
            'id' => $this->getId(),
            'ca_certificate' => $this->getCaCertificate(),
            'client_certificate' => $this->getClientCertificate(),
            'client_key' => $this->getClientKey(),
            'token' => $this->getToken(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
        ];

        $this->setGroupsConfiguration(self::$exportConfigurations);

        $normalizer->injectData(
            $this->filterExport(
                $data,
                (array) ($context['groups'] ?? ['default']),
            )
        );

        return $this;
    }
}
