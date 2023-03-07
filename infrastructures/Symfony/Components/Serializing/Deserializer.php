<?php

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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Service, built on Symfony Serializer, able to deserialize json object to an PHP object of this library.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Deserializer implements DeserializerInterface
{
    public function __construct(
        private readonly SymfonySerializerInterface $serializer,
    ) {
    }

    public function deserialize(
        string $data,
        string $type,
        string $format,
        PromiseInterface $promise,
        array $context = []
    ): DeserializerInterface {
        try {
            $promise->success(
                $this->serializer->deserialize(
                    data: $data,
                    type: $type,
                    format: $format,
                    context: $context
                )
            );
        } catch (Throwable $throwable) {
            $promise->fail($throwable);
        }

        return $this;
    }
}
