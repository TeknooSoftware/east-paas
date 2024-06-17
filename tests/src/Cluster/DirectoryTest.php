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

namespace Teknoo\Tests\East\Paas\Cluster;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Directory::class)]
class DirectoryTest extends TestCase
{
    public function buildDirectory()
    {
        return new Directory();
    }

    public function testRegisterBadType()
    {
        $this->expectException(\TypeError::class);

        $this->buildDirectory()->register(
            new \stdClass(),
            $this->createMock(DriverInterface::class)
        );
    }

    public function testRegisterBadClient()
    {
        $this->expectException(\TypeError::class);

        $this->buildDirectory()->register(
            'foo',
            new \stdClass()
        );
    }

    public function testRegister()
    {
        self::assertInstanceOf(
            Directory::class,
            $this->buildDirectory()->register(
                'foo',
                $this->createMock(DriverInterface::class)
            )
        );
    }

    public function testRequireBadType()
    {
        $this->expectException(\TypeError::class);

        $this->buildDirectory()->require(
            new \stdClass(),
            $this->createMock(Cluster::class),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testRequireBadCluster()
    {
        $this->expectException(\TypeError::class);

        $this->buildDirectory()->require(
            'foo',
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testRequireBadPromise()
    {
        $this->expectException(\TypeError::class);

        $this->buildDirectory()->require(
            'foo',
            $this->createMock(Cluster::class),
            new \stdClass()
        );
    }

    public function testRequireNotFound()
    {
        $directory = $this->buildDirectory();

        self::assertInstanceOf(
            Directory::class,
            $directory->register(
                'foo',
                $this->createMock(DriverInterface::class)
            )
        );

        $cluster = $this->createMock(Cluster::class);
        $cluster->expects($this->never())->method('configureCluster');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            Directory::class,
            $directory->require(
                'bar',
                $this->createMock(DefaultsBag::class),
                $cluster,
                $promise
            )
        );
    }

    public function testRequireFound()
    {
        $directory = $this->buildDirectory();

        self::assertInstanceOf(
            Directory::class,
            $directory->register(
                'foo',
                $this->createMock(DriverInterface::class)
            )
        );

        $cluster = $this->createMock(Cluster::class);
        $cluster->expects($this->once())->method('configureCluster');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            Directory::class,
            $directory->require(
                'foo',
                $this->createMock(DefaultsBag::class),
                $cluster,
                $promise
            )
        );
    }
}