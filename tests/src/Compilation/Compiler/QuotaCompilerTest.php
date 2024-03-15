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

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory;
use Teknoo\East\Paas\Compilation\Compiler\QuotaCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Compilation\Compiler\QuotaCompiler
 */
class QuotaCompilerTest extends TestCase
{
    public function buildCompiler(): QuotaCompiler
    {
        $factory = $this->createMock(Factory::class);

        return new QuotaCompiler($factory);
    }

    private function getDefinitionsArray(): array
    {
        return [
            [
                'category' => 'compute',
                'type' => 'cpu',
                'capacity' => 2,
                'requires' => 1,
            ],
            [
                'category' => 'memory',
                'type' => 'memory',
                'capacity' => '512Mi',
            ]
        ];
    }

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];

        $manager = $this->createMock(ResourceManager::class);
        $manager->expects(self::never())->method('updateQuotaAvailability');
        $manager->expects(self::once())->method('freeze');

        self::assertInstanceOf(
            QuotaCompiler::class,
            $this->buildCompiler()->compile(
                $definitions,
                $this->createMock(CompiledDeploymentInterface::class),
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $manager,
                $this->createMock(DefaultsBag::class),
            )
        );
    }

    public function testCompile()
    {
        $definitions = $this->getDefinitionsArray();

        $manager = $this->createMock(ResourceManager::class);
        $manager->expects(self::exactly(2))->method('updateQuotaAvailability');
        $manager->expects(self::once())->method('freeze');

        self::assertInstanceOf(
            QuotaCompiler::class,
            $this->buildCompiler()->compile(
                $definitions,
                $this->createMock(CompiledDeploymentInterface::class),
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $manager,
                $this->createMock(DefaultsBag::class),
            )
        );
    }
}
