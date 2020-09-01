<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExceptionNormalizer implements NormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function normalize($object, string $format = null, array $context = array()): array
    {
        if (!$object instanceof \Throwable) {
            throw new \LogicException('app.normalizer.exception.non_manager');
        }

        return [
            'class' => \get_class($object),
            'message' => $object->getMessage(),
            'code' => $object->getCode(),
            'file' => $object->getFile(),
            'line' => $object->getLine(),
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof \Throwable;
    }
}
