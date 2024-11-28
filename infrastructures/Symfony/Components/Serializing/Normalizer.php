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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Service, built on Symfony Normalizer, able to serialize a PHP object to an array.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Normalizer implements NormalizerInterface
{
    public function __construct(
        private readonly SymfonyNormalizerInterface $normalizer,
    ) {
    }

    public function normalize(
        mixed $object,
        PromiseInterface $promise,
        ?string $format = null,
        array $context = []
    ): NormalizerInterface {
        try {
            $promise->success($this->normalizer->normalize($object, $format, $context));
        } catch (Throwable $throwable) {
            $promise->fail($throwable);
        }

        return $this;
    }
}
