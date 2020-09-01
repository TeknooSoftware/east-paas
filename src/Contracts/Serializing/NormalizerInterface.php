<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Serializing;

use Teknoo\East\Foundation\Promise\PromiseInterface;

interface NormalizerInterface
{
    /**
     * @param mixed $object
     * @param array<string, mixed> $context
     */
    public function normalize(
        $object,
        PromiseInterface $promise,
        string $format = null,
        array $context = []
    ): NormalizerInterface;
}
