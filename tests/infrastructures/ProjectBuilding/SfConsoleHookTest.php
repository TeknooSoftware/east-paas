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

namespace Teknoo\Tests\East\Paas\Infrastructures\ProjectBuilding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use PHPUnit\Framework\MockObject\Stub;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\AbstractHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Contracts\ProcessFactoryInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\SfConsoleHook;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SfConsoleHook::class)]
#[CoversClass(AbstractHook::class)]
class SfConsoleHookTest extends TestCase
{
    public function publicCreateMock(string $originalClassName): MockObject|Stub
    {
        return parent::createStub($originalClassName);
    }

    public function buildHook(bool $success = true): SfConsoleHook
    {
        return new SfConsoleHook(
            __DIR__ . '/../../../sfConsole.phar',
            10,
            new readonly class ($success, $this) implements ProcessFactoryInterface {
                public function __construct(
                    private bool $success,
                    private SfConsoleHookTest $test,
                ) {
                }

                public function __invoke(array $command, string $cwd, float $timeout): Process
                {
                    $process = $this->test->publicCreateMock(Process::class);
                    $process->method('isSuccessful')->willReturn($this->success);

                    return $process;
                }
            },
        );
    }

    public function testSetPathBadPath(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setPath(new \stdClass());
    }

    public function testSetPath(): void
    {
        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setPath('/foo'));
    }

    public function testSetOptionsBadOptions(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setOptions(new \stdClass(), $this->createStub(PromiseInterface::class));
    }

    public function testSetOptionsNotScalar(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->buildHook()->setOptions(['foo' => new \stdClass()], $promise);
    }

    public function testSetOptionsBadPromise(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setOptions([], new \stdClass());
    }

    public function testSetOptionsWithPipe(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setOptions(['install || rm -r /'], $promise));
    }

    public function testSetOptionsWithAnd(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setOptions(['install && rm -r /'], $promise));
    }

    public function testSetOptionsWithNonSaclarArguments(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setOptions(['action' => 'install', 'arguments' => [['foo']]], $promise));
    }

    public function testSetOptionsWithPipeInArguments(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['--no-dev || rm -r /']], $promise));
    }

    public function testSetOptionsWithAndInArguments(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['--no-dev && rm -r /']], $promise));
    }

    public function testSetOptions(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->method('success');
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setOptions(['install'], $promise));
    }

    public function testSetOptionsWithAction(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->method('success');
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setOptions(['action' => 'install'], $promise));
    }

    public function testSetOptionsWithActionAndArguments(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->method('success');
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['prefer-install']], $promise));
    }

    public function testRunProcessSuccess(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook(true)->run($promise));
    }

    public function testRunProcessFail(): void
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(SfConsoleHook::class, $this->buildHook(false)->run($promise));
    }
}
