<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Job;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\Job
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\MessageTrait
 */
class JobTest extends TestCase
{
    use MessageTestTrait;

    /**
     * @inheritDoc
     */
    public function buildMessage()
    {
        return new Job('fooBar');
    }
}
