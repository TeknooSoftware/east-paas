<?php

declare(strict_types=1);

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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Writer;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\East\Paas\Writer\ClusterWriter;
use Teknoo\Tests\East\Common\Writer\PersistTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Writer\ClusterWriter
 */
class ClusterWriterTest extends TestCase
{
    use PersistTestTrait;

    public function buildWriter(bool $prefereRealDateOnUpdate = false,): WriterInterface
    {
        return new ClusterWriter(manager: $this->getObjectManager(), prefereRealDateOnUpdate: $prefereRealDateOnUpdate);
    }

    public function getObject()
    {
        return new Cluster();
    }
}
