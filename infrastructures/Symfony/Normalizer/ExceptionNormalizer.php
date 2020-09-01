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

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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
