<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Normalizer;

use LogicException;
use SensitiveParameter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;

/**
 * Symfony normalizer dedicated to Throwable object.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ExceptionNormalizer implements NormalizerInterface
{
    /**
     * @param array<string, mixed> $context
     * @return array{class: class-string<\Throwable>&string, message: string, code: int|string, file: string, line: int}
     */
    public function normalize(#[SensitiveParameter] $object, $format = null, array $context = []): array
    {
        if (!$object instanceof Throwable) {
            throw new LogicException('teknoo.east.paas.normalizer.exception.non_manager');
        }

        return [
            'class' => $object::class,
            'message' => $object->getMessage(),
            'code' => $object->getCode(),
            'file' => $object->getFile(),
            'line' => $object->getLine(),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Throwable;
    }

    /**
     * @return array<class-string|'*'|'object'|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Throwable::class => false,
        ];
    }
}
