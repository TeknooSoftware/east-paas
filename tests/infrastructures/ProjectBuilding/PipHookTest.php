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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\ProjectBuilding;

use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\PipHook;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use function str_replace;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\ProjectBuilding\AbstractHook
 * @covers \Teknoo\East\Paas\Infrastructures\ProjectBuilding\PipHook
 */
class PipHookTest extends TestCase
{
    public function buildHook(
        bool $success = true,
        ?array $expectedArguments = null,
        string|array $bin = __DIR__ . '/../../../pip.phar',
    ): PipHook {
        return new PipHook(
            $bin,
            function (array $args) use ($bin, $success, $expectedArguments) {
                if (null !== $expectedArguments) {
                    $bin = str_replace('${PWD}', '/foo', $bin);
                    self::assertEquals([...((array) $bin), ...$expectedArguments], $args);
                }

                $process = $this->createMock(Process::class);
                $process->expects(self::any())->method('isSuccessful')->willReturn($success);

                return $process;
            }
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
            PipHook::class,
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
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

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
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['install || rm -r /'], $promise)
        );
    }

    public function testSetOptionsWithAnd()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['install && rm -r /'], $promise)
        );
    }

    public function testSetOptionsWithNonSaclarArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => [['foo']]], $promise)
        );
    }

    public function testSetOptionsWithPipeInArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['--no-dev || rm -r /']], $promise)
        );
    }

    public function testSetOptionsWithAndInArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['--no-dev && rm -r /']], $promise)
        );
    }

    public function testSetOptionsWithForbiddenCommand()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['global', 'install'], $promise)
        );
    }

    public function testSetOptions()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::any())->method('success');
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['install'], $promise)
        );
    }

    public function testSetOptionsWithAction()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::any())->method('success');
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['action' => 'install'], $promise)
        );
    }

    public function testSetOptionsWithActionAndArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::any())->method('success');
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['force-reinstall', 'r', 'myfile.txt']], $promise)
        );
    }

    public function testRunNotSfProcess()
    {
        $hook = new PipHook(
            __DIR__ . '/../../../pip.phar',
            static fn() => new \stdClass()
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $hook->run($promise)
        );
    }

    public function testRunProcessSuccess()
    {
        $promiseOpt = $this->createMock(PromiseInterface::class);
        $promiseOpt->expects(self::once())->method('success');
        $promiseOpt->expects(self::never())->method('fail');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook(
                    true,
                    [
                        'install',
                        '--force-reinstall',
                        '-r',
                        'myfile.txt',
                    ]
                )
                ->setOptions(['action' => 'install', 'arguments' => ['force-reinstall', 'r', 'myfile.txt']], $promiseOpt)
                ->run($promise)
        );
    }

    public function testRunProcessSuccessWithArray()
    {
        $promiseOpt = $this->createMock(PromiseInterface::class);
        $promiseOpt->expects(self::once())->method('success');
        $promiseOpt->expects(self::never())->method('fail');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook(
                    success: true,
                    expectedArguments: [
                        'install',
                        '--force-reinstall',
                        '-r',
                        'myfile.txt',
                    ],
                    bin: [
                        __DIR__ . '/../../../pip.phar',
                        '--',
                    ]
                )
                ->setOptions(['action' => 'install', 'arguments' => ['force-reinstall', 'r', 'myfile.txt']], $promiseOpt)
                ->run($promise)
        );
    }

    public function testRunProcessSuccessWithPWD()
    {
        $promiseOpt = $this->createMock(PromiseInterface::class);
        $promiseOpt->expects(self::once())->method('success');
        $promiseOpt->expects(self::never())->method('fail');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook(
                    success: true,
                    expectedArguments: [
                        'install',
                        '--force-reinstall',
                        '-r',
                        'myfile.txt',
                    ],
                    bin: [
                        __DIR__ . '/../../../pip.phar',
                        '--',
                        '${PWD}',
                    ]
                )
                ->setOptions(['action' => 'install', 'arguments' => ['force-reinstall', 'r', 'myfile.txt']], $promiseOpt)
                ->setPath('/foo')
                ->run($promise)
        );
    }

    public function testRunProcessFail()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        self::assertInstanceOf(
            PipHook::class,
            $this->buildHook(false)->run($promise)
        );
    }
}
