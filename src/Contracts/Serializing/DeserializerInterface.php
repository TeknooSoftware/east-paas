<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Serializing;

use Teknoo\East\Foundation\Promise\PromiseInterface;

interface DeserializerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function deserialize(
        string $data,
        string $type,
        string $format,
        PromiseInterface $promise,
        array $context = []
    ): DeserializerInterface;
}
