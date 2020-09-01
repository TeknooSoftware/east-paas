<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Writer;

use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Writer\PersistTrait;
use Teknoo\East\Website\Writer\WriterInterface;

class JobWriter implements WriterInterface
{
    use PersistTrait;

    public function save(ObjectInterface $object, PromiseInterface $promise = null): WriterInterface
    {
        $this->persist($object, $promise);

        return $this;
    }
}
