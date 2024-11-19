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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirement\Set;
use Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirementCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\FeaturesRequirement\ValidatorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(FeaturesRequirementCompiler::class)]
class FeaturesRequirementCompilerTest extends TestCase
{
    public function buildCompiler(): FeaturesRequirementCompiler
    {
        return new FeaturesRequirementCompiler([
            new class implements ValidatorInterface {
                public function __invoke(Set $requirements): void
                {
                    $requirements->validate('hello');
                }
            }
        ]);
    }

    public function testInvalidValidator()
    {
        $this->expectException(\InvalidArgumentException::class);
        new FeaturesRequirementCompiler([new \stdClass()]);
    }

    public function testAddValidator()
    {
        self::assertInstanceOf(
            FeaturesRequirementCompiler::class,
            $this->buildCompiler()->addValidator(
                new class implements ValidatorInterface {
                    public function __invoke(Set $requirements): void
                    {
                        $requirements->validate('world');
                    }
                }
            )
        );
    }

    public function testCompileWithValidatedRequirements()
    {
        $compiler = $this->buildCompiler();

        self::assertInstanceOf(
            FeaturesRequirementCompiler::class,
            $compiler->addValidator(
                new class implements ValidatorInterface {
                    public function __invoke(Set $requirements): void
                    {
                        $requirements->validate('world');
                    }
                }
            )
        );

        $reqs = ['hello', 'world'];
        self::assertInstanceOf(
            FeaturesRequirementCompiler::class,
            $compiler->compile(
                $reqs,
                $this->createMock(CompiledDeploymentInterface::class),
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
                $this->createMock(DefaultsBag::class),
            )
        );
    }

    public function testCompileWithEmptyRequirements()
    {
        $compiler = $this->buildCompiler();

        $reqs = [];
        self::assertInstanceOf(
            FeaturesRequirementCompiler::class,
            $compiler->compile(
                $reqs,
                $this->createMock(CompiledDeploymentInterface::class),
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
                $this->createMock(DefaultsBag::class),
            )
        );
    }

    public function testCompileWithoutValidatedRequirements()
    {
        $compiler = $this->buildCompiler();

        $reqs = ['hello', 'world'];

        $this->expectException(\DomainException::class);
        self::assertInstanceOf(
            FeaturesRequirementCompiler::class,
            $compiler->compile(
                $reqs,
                $this->createMock(CompiledDeploymentInterface::class),
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class),
                $this->createMock(ResourceManager::class),
                $this->createMock(DefaultsBag::class),
            )
        );
    }
}
