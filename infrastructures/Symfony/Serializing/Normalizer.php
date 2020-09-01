<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface as SymfonyNormalizerInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

class Normalizer implements NormalizerInterface
{
    private SymfonyNormalizerInterface $normalizer;

    public function __construct(SymfonyNormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param mixed $object
     */
    public function normalize(
        $object,
        PromiseInterface $promise,
        string $format = null,
        array $context = []
    ): NormalizerInterface {
        try {
            $promise->success($this->normalizer->normalize($object, $format, $context));
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
