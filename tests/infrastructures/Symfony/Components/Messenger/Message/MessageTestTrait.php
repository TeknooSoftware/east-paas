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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Error;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait MessageTestTrait
{
    abstract public function buildMessage();

    public function testContructorUnique(): void
    {
        $this->expectException(Error::class);
        $this->buildMessage()->__construct('foo', 'bar', 'hello', 'world');
    }

    public function testGetProjectId(): void
    {
        $this->assertEquals(
            'foo',
            $this->buildMessage()->getProjectId()
        );
    }

    public function testGetEnvironment(): void
    {
        $this->assertEquals(
            'bar',
            $this->buildMessage()->getEnvironment()
        );
    }

    public function testGetJobId(): void
    {
        $this->assertEquals(
            'hello',
            $this->buildMessage()->getJobId()
        );
    }

    public function testGetMessage(): void
    {
        $this->assertEquals(
            'world',
            $this->buildMessage()->getMessage()
        );
    }

    public function testGetContent(): void
    {
        $this->assertEquals(
            'world',
            $this->buildMessage()->getContent()
        );
    }

    public function testToString(): void
    {
        $this->assertEquals(
            'world',
            (string) $this->buildMessage()
        );
    }

    public function testGetEncryptionAlgorithm(): void
    {
        $this->assertNull(
            $this->buildMessage()->getEncryptionAlgorithm()
        );
    }

    public function testCloneWith(): void
    {
        $messageA = $this->buildMessage();
        $messageB = $messageA->cloneWith('monde', 'anAlgo');

        $this->assertEquals(
            $messageA::class,
            $messageB::class,
        );

        $this->assertEquals(
            'foo',
            $messageA->getProjectId()
        );

        $this->assertEquals(
            'foo',
            $messageB->getProjectId()
        );

        $this->assertEquals(
            'bar',
            $messageA->getEnvironment()
        );

        $this->assertEquals(
            'bar',
            $messageB->getEnvironment()
        );

        $this->assertEquals(
            'hello',
            $messageA->getJobId()
        );

        $this->assertEquals(
            'hello',
            $messageB->getJobId()
        );

        $this->assertEquals(
            'world',
            $messageA->getMessage()
        );

        $this->assertEquals(
            'monde',
            $messageB->getMessage()
        );

        $this->assertNull(
            $messageA->getEncryptionAlgorithm()
        );

        $this->assertEquals(
            'anAlgo',
            $messageB->getEncryptionAlgorithm()
        );
    }
}
