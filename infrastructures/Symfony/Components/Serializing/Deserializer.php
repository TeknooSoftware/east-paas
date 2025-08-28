<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use SensitiveParameter;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Service, built on Symfony Serializer, able to deserialize json object to an PHP object of this library.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Deserializer implements DeserializerInterface
{
    public function __construct(
        private readonly SymfonySerializerInterface $serializer,
    ) {
    }

    public function deserialize(
        #[SensitiveParameter] string $data,
        string $type,
        string $format,
        PromiseInterface $promise,
        array $context = [],
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
