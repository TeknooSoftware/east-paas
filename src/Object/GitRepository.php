<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Foundation\Normalizer\EastNormalizerInterface;
use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;

class GitRepository implements
    ObjectInterface,
    ImmutableInterface,
    SourceRepositoryInterface,
    NormalizableInterface,
    TimestampableInterface
{
    use ObjectTrait;
    use ImmutableTrait;

    private ?string $pullUrl = null;

    private ?string $defaultBranch = null;

    private ?IdentityInterface $identity = null;

    public function __construct(
        string $pullUrl = '',
        string $defaultBranch = 'master',
        IdentityInterface $identity = null
    ) {
        $this->uniqueConstructorCheck();

        $this->pullUrl = $pullUrl;
        $this->defaultBranch = $defaultBranch;
        $this->identity = $identity;
    }

    public function getPullUrl(): string
    {
        return (string) $this->pullUrl;
    }

    public function getDefaultBranch(): string
    {
        return (string) $this->defaultBranch;
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
