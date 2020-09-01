<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

class Deserializer implements DeserializerInterface
{
    private SymfonySerializerInterface $serializer;

    public function __construct(SymfonySerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function deserialize(
        string $data,
        string $type,
        string $format,
        PromiseInterface $promise,
        array $context = []
    ): DeserializerInterface {
        try {
            $promise->success($this->serializer->deserialize($data, $type, $format, $context));
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
