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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\MessageTrait;
use Teknoo\Immutable\Exception\ImmutableException;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait MessageTestTrait
{
    /**
     * @return MessageTrait
     */
    abstract public function buildMessage();

    public function testContructorUnique()
    {
        $this->expectException(\Error::class);
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

    public function testGetContent()
    {
        self::assertEquals(
            'world',
            $this->buildMessage()->getContent()
        );
    }

    public function testToString()
    {
        self::assertEquals(
            'world',
            (string) $this->buildMessage()
        );
    }

    public function testGetEncryptionAlgorithm()
    {
        self::assertNull(
            $this->buildMessage()->getEncryptionAlgorithm()
        );
    }

    public function testCloneWith()
    {
        $messageA = $this->buildMessage();
        $messageB = $messageA->cloneWith('monde', 'anAlgo');

        self::assertEquals(
            $messageA::class,
            $messageB::class,
        );

        self::assertEquals(
            'foo',
            $messageA->getProjectId()
        );

        self::assertEquals(
            'foo',
            $messageB->getProjectId()
        );

        self::assertEquals(
            'bar',
            $messageA->getEnvironment()
        );

        self::assertEquals(
            'bar',
            $messageB->getEnvironment()
        );

        self::assertEquals(
            'hello',
            $messageA->getJobId()
        );

        self::assertEquals(
            'hello',
            $messageB->getJobId()
        );

        self::assertEquals(
            'world',
            $messageA->getMessage()
        );

        self::assertEquals(
            'monde',
            $messageB->getMessage()
        );

        self::assertNull(
            $messageA->getEncryptionAlgorithm()
        );

        self::assertEquals(
            'anAlgo',
            $messageB->getEncryptionAlgorithm()
        );
    }
}
