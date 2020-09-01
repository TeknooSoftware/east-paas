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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Composer;

use Teknoo\East\Paas\Infrastructures\Composer\ComposerHook;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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