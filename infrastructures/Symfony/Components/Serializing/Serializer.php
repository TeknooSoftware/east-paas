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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Service, built on Symfony Serializer able to serialize a PHP object to a json object as string.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Serializer implements SerializerInterface
{
    public function __construct(
        private SymfonySerializerInterface $serializer,
    ) {
    }

    public function serialize(
        mixed $data,
        string $format,
        PromiseInterface $promise,
        array $context = []
    ): SerializerInterface {
        try {
            $promise->success($this->serializer->serialize($data, $format, $context));
        } catch (Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
