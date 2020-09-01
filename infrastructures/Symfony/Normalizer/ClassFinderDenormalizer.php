<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ClassFinderDenormalizer implements DenormalizerAwareInterface, DenormalizerInterface
{
    private ?DenormalizerInterface $denormalizer = null;

    public function setDenormalizer(DenormalizerInterface $denormalizer): self
    {
        $this->denormalizer = $denormalizer;

        return $this;
    }

    /**
     * @param mixed $data
     * @param array<string, mixed> $context
     * @return array|object
     */
    public function denormalize($data, string $class, string $format = null, array $context = array())
    {
        if (
            !$this->denormalizer instanceof DenormalizerInterface
            || !\is_array($data)
            || empty($data['@class'])
            || !\class_exists($data['@class'], true)
        ) {
            throw new \RuntimeException('Error, this object is not managed by this denormalizer');
        }

        $class = $data['@class'];
        unset($data['@class']);

        return $this->denormalizer->denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, string $format = null): bool
    {
        return $this->denormalizer instanceof DenormalizerInterface
            && \is_array($data)
            && !empty($data['@class'])
            && \class_exists($data['@class'], true);
    }
}
