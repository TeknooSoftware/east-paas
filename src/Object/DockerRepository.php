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
use Teknoo\East\Paas\Contracts\Object\ImagesRepositoryInterface;

class DockerRepository implements
    ObjectInterface,
    ImmutableInterface,
    ImagesRepositoryInterface,
    NormalizableInterface,
    TimestampableInterface
{
    use ObjectTrait;
    use ImmutableTrait;

    private ?string $name = null;

    private ?string $apiUrl = null;

    private ?IdentityInterface $identity = null;

    public function __construct(
        string $name = '',
        string $apiUrl = '',
        ?IdentityInterface $identity = null
    ) {
        $this->uniqueConstructorCheck();

        $this->name = $name;
        $this->apiUrl = $apiUrl;
        $this->identity = $identity;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getApiUrl(): string
    {
        return (string) $this->apiUrl;
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
           'name' => $this->getName(),
           'api_url' => $this->getApiUrl(),
           'identity' => $this->getIdentity(),
        ]);

        return $this;
    }
}
