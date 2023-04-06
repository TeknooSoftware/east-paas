<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Serializing\Exception\MissingClassAttributeException;

use function class_exists;
use function is_array;

/**
 * Symfony Denormalizer to find in the json's attribuute `@class` the true type of the object to help
 * Symfony denormalizer to select the good denormalizer dedicated to its class.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ClassFinderDenormalizer implements DenormalizerAwareInterface, DenormalizerInterface
{
    private ?DenormalizerInterface $denormalizer = null;

    public function setDenormalizer(DenormalizerInterface $denormalizer): self
    {
        $this->denormalizer = $denormalizer;

        return $this;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(
        mixed $data,
        string $class,
        string $format = null,
        array $context = []
    ): array | object {
        if (
            !$this->denormalizer instanceof DenormalizerInterface
            || !is_array($data)
            || empty($data['@class'])
            || !class_exists($data['@class'], true)
        ) {
            throw new MissingClassAttributeException('Error, this object is not managed by this denormalizer');
        }

        $decodedClass = $data['@class'];
        unset($data['@class']);

        return $this->denormalizer->denormalize($data, $decodedClass, $format, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $this->denormalizer instanceof DenormalizerInterface
            && is_array($data)
            && !empty($data['@class'])
            && class_exists($data['@class'], true);
    }
}
