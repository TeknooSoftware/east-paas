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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\MessageTrait;
use Teknoo\Immutable\Exception\ImmutableException;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait MessageTestTrait
{
    /**
     * @return MessageTrait
     */
    abstract public function buildMessage();

    public function testContructorUnique()
    {
        $this->expectException(ImmutableException::class);
        $this->buildMessage()->__construct('foo', 'bar', 'hello', 'world');
    }

    public function testGetProjectId()
    {
        self::assertEquals(
            'foo',
            $this->buildMessage()->getProjectId()
        );
    }

    public function testGetEnvironment()
    {
        self::assertEquals(
            'bar',
            $this->buildMessage()->getEnvironment()
        );
    }

    public function testGetJobId()
    {
        self::assertEquals(
            'hello',
            $this->buildMessage()->getJobId()
        );
    }

    public function testGetMessage()
    {
        self::assertEquals(
            'world',
            $this->buildMessage()->getMessage()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'world',
            (string) $this->buildMessage()
        );
    }
}
