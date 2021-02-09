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

namespace Teknoo\Tests\East\Paas\Recipe;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Recipe\AdditionalStepsList;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildImages;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\AdditionalStepsList
 */
class AdditionalStepsListTest extends TestCase
{
    public function testAddBadPriority()
    {
        $this->expectException(\TypeError::class);

        $object = new AdditionalStepsList();
        $object->add(new \stdClass(), function() {});
    }

    public function testAddBadCallable()
    {
        $this->expectException(\TypeError::class);

        $object = new AdditionalStepsList();
        $object->add(1, new \stdClass());
    }

    public function testAdd()
    {
        $object = new AdditionalStepsList();
        self::assertInstanceOf(
            AdditionalStepsList::class,
            $object->add(1, function() {})
        );
    }

    public function testGetIterator()
    {
        $object = new AdditionalStepsList();
        self::assertInstanceOf(
            AdditionalStepsList::class,
            $object->add(1, function() {})
        );

        self::assertInstanceOf(
            AdditionalStepsList::class,
            $object->add(2, function() {})
        );

        $count = 1;
        foreach ($object as $p => $step) {
            self::assertEquals($count++, $p);
            self::assertInstanceOf(\Closure::class, $step);
        }

        self::assertEquals(3, $count);
    }
}
