<?php

declare(strict_types=1);

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

namespace Teknoo\Tests\East\Paas\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Writer\ProjectWriter;
use Teknoo\Tests\East\Common\Writer\PersistTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ProjectWriter::class)]
class ProjectWriterTest extends TestCase
{
    use PersistTestTrait;

    public function buildWriter(bool $preferRealDateOnUpdate = false): WriterInterface
    {
        return new ProjectWriter(manager: $this->getObjectManager(), preferRealDateOnUpdate: $preferRealDateOnUpdate);
    }

    /**
     * @return Project
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function getObject()
    {
        return new Project(new Account());
    }
}
