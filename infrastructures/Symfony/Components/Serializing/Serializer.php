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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Serializing;

use SensitiveParameter;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Service, built on Symfony Serializer able to serialize a PHP object to a json object as string.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Serializer implements SerializerInterface
{
    public function __construct(
        private readonly SymfonySerializerInterface $serializer,
    ) {
    }

    public function serialize(
        #[SensitiveParameter] mixed $data,
        string $format,
        PromiseInterface $promise,
        array $context = []
    ): SerializerInterface {
        try {
            $promise->success($this->serializer->serialize($data, $format, $context));
        } catch (Throwable $throwable) {
            $promise->fail($throwable);
        }

        return $this;
    }
}
