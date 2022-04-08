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

namespace Teknoo\Tests\East\Paas\Writer;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\Tests\East\Common\Writer\PersistTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Writer\JobWriter
 */
class JobWriterTest extends TestCase
{
    use PersistTestTrait;

    public function buildWriter(): WriterInterface
    {
        return new JobWriter($this->getObjectManager());
    }

    /**
     * @return Job
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function getObject()
    {
        return new Job($this->createMock(Project::class));
    }
}
