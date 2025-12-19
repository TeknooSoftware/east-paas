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

namespace Teknoo\Tests\East\Paas\Cluster;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Cluster\DriverInterface;
use Teknoo\East\Paas\Object\Cluster;
use Teknoo\Recipe\Promise\PromiseInterface;
use TypeError;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Directory::class)]
class DirectoryTest extends TestCase
{
    public function buildDirectory(): Directory
    {
        return new Directory();
    }

    public function testRegisterBadType(): void
    {
        $this->expectException(TypeError::class);

        $this->buildDirectory()->register(
            new stdClass(),
            $this->createStub(DriverInterface::class)
        );
    }

    public function testRegisterBadClient(): void
    {
        $this->expectException(TypeError::class);

        $this->buildDirectory()->register(
            'foo',
            new stdClass()
        );
    }

    public function testRegister(): void
    {
        $this->assertInstanceOf(Directory::class, $this->buildDirectory()->register(
            'foo',
            $this->createStub(DriverInterface::class)
        ));
    }

    public function testRequireBadType(): void
    {
        $this->expectException(TypeError::class);

        $this->buildDirectory()->require(
            new stdClass(),
            $this->createStub(Cluster::class),
            $this->createStub(PromiseInterface::class)
        );
    }

    public function testRequireBadCluster(): void
    {
        $this->expectException(TypeError::class);

        $this->buildDirectory()->require(
            'foo',
            new stdClass(),
            $this->createStub(PromiseInterface::class)
        );
    }

    public function testRequireBadPromise(): void
    {
        $this->expectException(TypeError::class);

        $this->buildDirectory()->require(
            'foo',
            $this->createStub(Cluster::class),
            new stdClass()
        );
    }

    public function testRequireNotFound(): void
    {
        $directory = $this->buildDirectory();

        $this->assertInstanceOf(Directory::class, $directory->register(
            'foo',
            $this->createStub(DriverInterface::class)
        ));

        $cluster = $this->createMock(Cluster::class);
        $cluster->expects($this->never())->method('configureCluster');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $this->assertInstanceOf(Directory::class, $directory->require(
            'bar',
            $this->createStub(DefaultsBag::class),
            $cluster,
            $promise
        ));
    }

    public function testRequireFound(): void
    {
        $directory = $this->buildDirectory();

        $this->assertInstanceOf(Directory::class, $directory->register(
            'foo',
            $this->createStub(DriverInterface::class)
        ));

        $cluster = $this->createMock(Cluster::class);
        $cluster->expects($this->once())->method('configureCluster');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->assertInstanceOf(Directory::class, $directory->require(
            'foo',
            $this->createStub(DefaultsBag::class),
            $cluster,
            $promise
        ));
    }
}
