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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Object\ObjectTrait;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;

/**
 * Immutable object storing data needed to fetch a project from a source repository.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class GitRepository implements
    IdentifiedObjectInterface,
    ImmutableInterface,
    SourceRepositoryInterface,
    NormalizableInterface,
    TimestampableInterface
{
    use ObjectTrait;
    use ImmutableTrait;

    private string $pullUrl = '';

    private string $defaultBranch = 'main';

    private ?IdentityInterface $identity = null;

    public function __construct(
        string $pullUrl = '',
        string $defaultBranch = 'main',
        ?IdentityInterface $identity = null,
    ) {
        $this->uniqueConstructorCheck();

        $this->pullUrl = $pullUrl;
        $this->defaultBranch = $defaultBranch;
        $this->identity = $identity;
    }

    public function getPullUrl(): string
    {
        return $this->pullUrl;
    }

    public function getDefaultBranch(): string
    {
        return $this->defaultBranch;
    }

    public function getIdentity(): ?IdentityInterface
    {
        return $this->identity;
    }

    public function exportToMeData(EastNormalizerInterface $normalizer, array $context = []): NormalizableInterface
    {
        $normalizer->injectData([
           '@class' => self::class,
           'id' => $this->getId(),
           'pull_url' => $this->getPullUrl(),
           'default_branch' => $this->getDefaultBranch(),
           'identity' => $this->getIdentity(),
        ]);

        return $this;
    }
}
