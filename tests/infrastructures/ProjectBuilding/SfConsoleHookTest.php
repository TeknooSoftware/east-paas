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

namespace Teknoo\Tests\East\Paas\Infrastructures\ProjectBuilding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\AbstractHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Contracts\ProcessFactoryInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\SfConsoleHook;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SfConsoleHook::class)]
#[CoversClass(AbstractHook::class)]
class SfConsoleHookTest extends TestCase
{
    public function publicCreateMock(string $originalClassName): MockObject
    {
        return parent::createMock($originalClassName);
    }

    public function publicAny(): AnyInvokedCountMatcher
    {
        return parent::any();
    }

    public function buildHook(bool $success = true): SfConsoleHook
    {
        return new SfConsoleHook(
            __DIR__ . '/../../../sfConsole.phar',
            10,
            new class($success, $this) implements ProcessFactoryInterface {
                public function __construct(
                    private bool $success,
                    private SfConsoleHookTest $test,
                ) {
                }

                public function __invoke(array $command, string $cwd, float $timeout): Process
                {
                    $process = $this->test->publicCreateMock(Process::class);
                    $process->expects($this->test->publicAny())->method('isSuccessful')->willReturn($this->success);

                    return $process;
                }
            },
        );
    }

    public function testSetPathBadPath()
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setPath(new \stdClass());
    }

    public function testSetPath()
    {
        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setPath('/foo')
        );
    }

    public function testSetOptionsBadOptions()
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setOptions(new \stdClass(), $this->createMock(PromiseInterface::class));
    }

    public function testSetOptionsNotScalar()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->buildHook()->setOptions(['foo' => new \stdClass()], $promise);
    }

    public function testSetOptionsBadPromise()
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setOptions([], new \stdClass());
    }

    public function testSetOptionsWithPipe()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setOptions(['install || rm -r /'], $promise)
        );
    }

    public function testSetOptionsWithAnd()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setOptions(['install && rm -r /'], $promise)
        );
    }

    public function testSetOptionsWithNonSaclarArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => [['foo']]], $promise)
        );
    }

    public function testSetOptionsWithPipeInArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['--no-dev || rm -r /']], $promise)
        );
    }

    public function testSetOptionsWithAndInArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['--no-dev && rm -r /']], $promise)
        );
    }

    public function testSetOptions()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->any())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setOptions(['install'], $promise)
        );
    }

    public function testSetOptionsWithAction()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->any())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setOptions(['action' => 'install'], $promise)
        );
    }

    public function testSetOptionsWithActionAndArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->any())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['prefer-install']], $promise)
        );
    }

    public function testRunProcessSuccess()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook(true)->run($promise)
        );
    }

    public function testRunProcessFail()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            SfConsoleHook::class,
            $this->buildHook(false)->run($promise)
        );
    }
}
