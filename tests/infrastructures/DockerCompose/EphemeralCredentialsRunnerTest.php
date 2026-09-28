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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\EphemeralCredentialsRunner;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\BadTempFileException;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(EphemeralCredentialsRunner::class)]
class EphemeralCredentialsRunnerTest extends TestCase
{
    /**
     * @param array<int, string> $deleted
     */
    private function buildFilesystem(array &$deleted): FilesystemOperator
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('deleteDirectory')->willReturnCallback(
            function (string $path) use (&$deleted): void {
                $deleted[] = $path;
            }
        );

        return $filesystem;
    }

    public function testRunDelegatesThenRemovesTheDirectory(): void
    {
        $deleted = [];
        $credentials = new ClusterCredentials(clientKey: 'KEY');
        $promise = $this->createStub(PromiseInterface::class);

        $inner = $this->createMock(RunnerInterface::class);
        $inner->expects($this->once())
            ->method('run')
            ->willReturnCallback(
                function (
                    string $playbookPath,
                    string $inventoryPath,
                    array $extraVars,
                    ?ClusterCredentials $receivedCredentials,
                    PromiseInterface $receivedPromise,
                ) use (&$deleted, $credentials, $promise, $inner): RunnerInterface {
                    //Files must still exist while the playbook runs
                    self::assertSame([], $deleted);
                    self::assertSame('/work/deploy.yml', $playbookPath);
                    self::assertSame('/work/inventory.ini', $inventoryPath);
                    self::assertSame(['paas_project' => 'foo'], $extraVars);
                    self::assertSame($credentials, $receivedCredentials);
                    self::assertSame($promise, $receivedPromise);

                    return $inner;
                }
            );

        $runner = new EphemeralCredentialsRunner(
            runner: $inner,
            filesystem: $this->buildFilesystem($deleted),
            directory: 'credentials-dir',
        );

        self::assertSame(
            $runner,
            $runner->run(
                playbookPath: '/work/deploy.yml',
                inventoryPath: '/work/inventory.ini',
                extraVars: ['paas_project' => 'foo'],
                credentials: $credentials,
                promise: $promise,
            ),
        );

        self::assertSame(['credentials-dir'], $deleted);

        //Already removed, the destructor must not remove it again
        unset($runner);
        self::assertSame(['credentials-dir'], $deleted);
    }

    public function testRunRemovesTheDirectoryWhenTheRunnerThrows(): void
    {
        $deleted = [];

        $inner = $this->createStub(RunnerInterface::class);
        $inner->method('run')->willThrowException(new RuntimeException('foo'));

        $runner = new EphemeralCredentialsRunner(
            runner: $inner,
            filesystem: $this->buildFilesystem($deleted),
            directory: 'credentials-dir',
        );

        try {
            $runner->run(
                playbookPath: '/work/deploy.yml',
                inventoryPath: '/work/inventory.ini',
                extraVars: [],
                credentials: null,
                promise: $this->createStub(PromiseInterface::class),
            );

            self::fail('The runner exception must be rethrown');
        } catch (RuntimeException $error) {
            self::assertSame('foo', $error->getMessage());
        }

        self::assertSame(['credentials-dir'], $deleted);
    }

    public function testRunTwiceFailsWithoutCallingTheRunnerAgain(): void
    {
        $deleted = [];

        $inner = $this->createMock(RunnerInterface::class);
        $inner->expects($this->once())->method('run');

        $runner = new EphemeralCredentialsRunner(
            runner: $inner,
            filesystem: $this->buildFilesystem($deleted),
            directory: 'credentials-dir',
        );

        $firstPromise = $this->createMock(PromiseInterface::class);
        $firstPromise->expects($this->never())->method('fail');

        $runner->run(
            playbookPath: '/work/deploy.yml',
            inventoryPath: '/work/inventory.ini',
            extraVars: [],
            credentials: null,
            promise: $firstPromise,
        );

        $secondPromise = $this->createMock(PromiseInterface::class);
        $secondPromise->expects($this->once())
            ->method('fail')
            ->with($this->isInstanceOf(BadTempFileException::class));

        self::assertSame(
            $runner,
            $runner->run(
                playbookPath: '/work/deploy.yml',
                inventoryPath: '/work/inventory.ini',
                extraVars: [],
                credentials: null,
                promise: $secondPromise,
            ),
        );

        self::assertSame(['credentials-dir'], $deleted);
    }

    public function testDestructRemovesTheDirectoryWhenNeverRun(): void
    {
        $deleted = [];

        $runner = new EphemeralCredentialsRunner(
            runner: $this->createStub(RunnerInterface::class),
            filesystem: $this->buildFilesystem($deleted),
            directory: 'credentials-dir',
        );

        self::assertSame([], $deleted);

        unset($runner);

        self::assertSame(['credentials-dir'], $deleted);
    }

    public function testRemovalFailureIsRethrownAndRetriedOnDestruction(): void
    {
        $deleted = [];
        $attempts = 0;

        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('deleteDirectory')->willReturnCallback(
            function (string $path) use (&$deleted, &$attempts): void {
                //The first removal fails, the retry on destruction succeeds
                if (0 === $attempts++) {
                    throw new RuntimeException('Unable to delete');
                }

                $deleted[] = $path;
            }
        );

        $runner = new EphemeralCredentialsRunner(
            runner: $this->createStub(RunnerInterface::class),
            filesystem: $filesystem,
            directory: 'credentials-dir',
        );

        try {
            $runner->run(
                playbookPath: '/work/deploy.yml',
                inventoryPath: '/work/inventory.ini',
                extraVars: [],
                credentials: null,
                promise: $this->createStub(PromiseInterface::class),
            );

            self::fail('The removal error must be rethrown');
        } catch (RuntimeException $error) {
            self::assertSame('Unable to delete', $error->getMessage());
        }

        self::assertSame([], $deleted);

        unset($runner);

        self::assertSame(['credentials-dir'], $deleted);
    }

    public function testDestructDoesNotThrowWhenTheRemovalFails(): void
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('deleteDirectory')->willThrowException(new RuntimeException('Unable to delete'));

        $runner = new EphemeralCredentialsRunner(
            runner: $this->createStub(RunnerInterface::class),
            filesystem: $filesystem,
            directory: 'credentials-dir',
        );

        unset($runner);

        $this->addToAssertionCount(1);
    }
}
