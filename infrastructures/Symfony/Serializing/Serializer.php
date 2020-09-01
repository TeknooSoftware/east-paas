<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

class Serializer implements SerializerInterface
{
    private SymfonySerializerInterface $serializer;

    public function __construct(SymfonySerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed $data
     */
    public function serialize(
        $data,
        string $format,
        PromiseInterface $promise,
        array $context = []
    ): SerializerInterface {
        try {
            $promise->success($this->serializer->serialize($data, $format, $context));
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
