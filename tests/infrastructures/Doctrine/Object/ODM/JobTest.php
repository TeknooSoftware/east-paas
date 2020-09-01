<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Doctrine\Object\ODM;

use Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM\Job
 */
class JobTest extends TestCase
{
    public function testStatesListDeclaration()
    {
        self::assertIsArray(Job::statesListDeclaration());
    }
}

