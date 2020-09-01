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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Hook;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;

class HooksCollection implements HooksCollectionInterface, ImmutableInterface
{
    use ImmutableTrait;

    /**
     * @var array<string, HookInterface>
     */
    private array $hooks;

    /**
     * @param array<string, HookInterface> $hooks
     */
    public function __construct(array $hooks)
    {
        $this->uniqueConstructorCheck();

        $this->hooks = $hooks;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->hooks as $name => $hook) {
            yield $name => $hook;
        }
    }
}
