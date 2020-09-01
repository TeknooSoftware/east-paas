<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
