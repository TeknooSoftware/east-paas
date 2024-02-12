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

use Stringable;
use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\GroupsTrait;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Paas\Object\Traits\ExportConfigurationsTrait;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;

/**
 * Immutable object storing data to perform authentication on a SSH server.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SshIdentity implements
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

    private string $name = '';

    private string $privateKey = '';

    /**
     * @var array<string, string[]>
     */
    private static array $exportConfigurations = [
        '@class' => ['default', 'api', 'crud'],
        'id' => ['default', 'api', 'crud'],
        'name' => ['default', 'api', 'crud'],
        'private_key' => ['default', 'crud'],
    ];

    public function __construct(
        string $name = '',
        string $privateKey = '',
    ) {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->privateKey = $privateKey;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $data = [
            '@class' => self::class,
            'id' => $this->getId(),
            'name' => $this->getName(),
            'private_key' => $this->getPrivateKey(),
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
