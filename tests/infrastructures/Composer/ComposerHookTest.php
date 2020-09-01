<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Composer;

use Teknoo\East\Paas\Infrastructures\Composer\ComposerHook;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @covers \Teknoo\East\Paas\Infrastructures\Composer\ComposerHook
 */
class ComposerHookTest extends TestCase
{
    public function buildHook(): ComposerHook
    {
        return new ComposerHook(
            __DIR__ . '/../../../composer.phar',
            function () {
                return $this->createMock(Process::class);
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
            ComposerHook::class,
            $this->buildHook()->setPath('/foo')
        );
    }

    public function testSetOptionsBadOptions()
    {
        $this->expectException(\TypeError::class);
        $this->buildHook()->setOptions(new \stdClass());
    }

    public function testSetOptions()
    {
        self::assertInstanceOf(
            ComposerHook::class,
            $this->buildHook()->setOptions(['foo' => 'bar'])
        );
    }

    public function testRunNotSfProcess()
    {
        $hook = new ComposerHook(
            __DIR__ . '/../../../composer.phar',
            static function () {
                return new \stdClass();
            }
        );

        $this->expectException(\RuntimeException::class);
        $hook->run();
    }

    public function testRun()
    {
        self::assertInstanceOf(
            ComposerHook::class,
            $this->buildHook()->run()
        );
    }
}
