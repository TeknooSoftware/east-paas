<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\East\Paas\Contracts\Serializing;

use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * To define a service able to deserialize json object to an PHP object of this library.
 *
 *@license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface DeserializerInterface
{
    /**
     * @param PromiseInterface<mixed, mixed> $promise
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
