<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Writer;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Writer\WriterInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Writer\ClusterWriter;
use Teknoo\Tests\East\Website\Writer\PersistTestTrait;

/**
 * @covers \Teknoo\East\Paas\Writer\ClusterWriter
 */
class ClusterWriterTest extends TestCase
{
    use PersistTestTrait;

    public function buildWriter(): WriterInterface
    {
        return new ClusterWriter($this->getObjectManager());
    }

    public function getObject()
    {
        return new Cluster();
    }
}
