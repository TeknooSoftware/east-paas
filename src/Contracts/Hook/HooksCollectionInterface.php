<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Hook;

interface HooksCollectionInterface extends \IteratorAggregate
{
    /**
     * @return \Traversable|HookInterface[]
     */
    public function getIterator(): \Traversable;
}
