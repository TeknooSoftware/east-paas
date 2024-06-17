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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\AbstractHook;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Contracts\ProcessFactoryInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\NpmHook;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use function str_replace;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(NpmHook::class)]
#[CoversClass(AbstractHook::class)]
class NpmHookTest extends TestCase
{
    public function publicCreateMock(string $originalClassName): MockObject
    {
        return parent::createMock($originalClassName);
    }

    public function publicAny(): AnyInvokedCountMatcher
    {
        return parent::any();
    }

    public function buildHook(
        bool $success = true,
        ?array $expectedArguments = null,
        string|array $bin = __DIR__ . '/../../../npm.phar',
    ): NpmHook {
        return new NpmHook(
            $bin,
            10,
            new class($bin, $success, $expectedArguments, $this) implements ProcessFactoryInterface {
                public function __construct(
                    private string|array $bin,
                    private bool $success,
                    private ?array $expectedArguments,
                    private NpmHookTest $test,
                ) {
                }

                public function __invoke(array $command, string $cwd, float $timeout): Process
                {
                    if (null !== $this->expectedArguments) {
                        $bin = str_replace('${PWD}', '/foo', $this->bin);
                        NpmHookTest::assertEquals([...((array) $bin), ...$this->expectedArguments], $command);
                    }

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
            NpmHook::class,
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
            NpmHook::class,
            $this->buildHook()->setOptions(['install || rm -r /'], $promise)
        );
    }

    public function testSetOptionsWithAnd()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['install && rm -r /'], $promise)
        );
    }

    public function testSetOptionsWithNonSaclarArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => [['foo']]], $promise)
        );
    }

    public function testSetOptionsWithPipeInArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['--no-dev || rm -r /']], $promise)
        );
    }

    public function testSetOptionsWithAndInArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['--no-dev && rm -r /']], $promise)
        );
    }

    public function testSetOptionsWithForbiddenCommand()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['global', 'install'], $promise)
        );
    }

    public function testSetOptions()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->any())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['install'], $promise)
        );
    }

    public function testSetOptionsWithAction()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->any())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['action' => 'install'], $promise)
        );
    }

    public function testSetOptionsWithActionAndArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->any())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['dry-run', 'foo']], $promise)
        );
    }

    public function testSetOptionsWithActionAndForbiddenArguments()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook()->setOptions(['action' => 'install', 'arguments' => ['&']], $promise)
        );
    }

    public function testRunProcessSuccess()
    {
        $promiseOpt = $this->createMock(PromiseInterface::class);
        $promiseOpt->expects($this->once())->method('success');
        $promiseOpt->expects($this->never())->method('fail');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook(
                    true,
                    [
                        'install',
                        '--dry-run',
                        'foo',
                    ]
                )
                ->setOptions(['action' => 'install', 'arguments' => ['dry-run', 'foo']], $promiseOpt)
                ->run($promise)
        );
    }

    public function testRunProcessSuccessWithArray()
    {
        $promiseOpt = $this->createMock(PromiseInterface::class);
        $promiseOpt->expects($this->once())->method('success');
        $promiseOpt->expects($this->never())->method('fail');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook(
                    success: true,
                    expectedArguments: [
                            'install',
                            '--dry-run',
                            'foo',
                    ],
                    bin: [
                        __DIR__ . '/../../../npm.phar',
                        '--',
                    ]
                )
                ->setOptions(['action' => 'install', 'arguments' => ['dry-run', 'foo']], $promiseOpt)
                ->run($promise)
        );
    }

    public function testRunProcessSuccessWithPWD()
    {
        $promiseOpt = $this->createMock(PromiseInterface::class);
        $promiseOpt->expects($this->once())->method('success');
        $promiseOpt->expects($this->never())->method('fail');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook(
                    success: true,
                    expectedArguments: [
                            'install',
                            '--dry-run',
                            'foo',
                    ],
                    bin: [
                        __DIR__ . '/../../../npm.phar',
                        '--',
                        '${PWD}',
                    ]
                )
                ->setOptions(['action' => 'install', 'arguments' => ['dry-run', 'foo']], $promiseOpt)
                ->setPath('/foo')
                ->run($promise)
        );
    }

    public function testRunProcessFail()
    {
        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            NpmHook::class,
            $this->buildHook(false)->run($promise)
        );
    }
}
