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

namespace Teknoo\Tests\East\Paas\Compilation\Compiler;

use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirement\Set;
use Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirementCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\FeaturesRequirement\ValidatorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(FeaturesRequirementCompiler::class)]
class FeaturesRequirementCompilerTest extends TestCase
{
    public function buildCompiler(): FeaturesRequirementCompiler
    {
        return new FeaturesRequirementCompiler([
            new class () implements ValidatorInterface {
                public function __invoke(Set $requirements): void
                {
                    $requirements->validate('hello');
                }
            }
        ]);
    }

    public function testInvalidValidator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FeaturesRequirementCompiler([new stdClass()]);
    }

    public function testAddValidator(): void
    {
        $this->assertInstanceOf(FeaturesRequirementCompiler::class, $this->buildCompiler()->addValidator(
            new class () implements ValidatorInterface {
                public function __invoke(Set $requirements): void
                {
                    $requirements->validate('world');
                }
            }
        ));
    }

    public function testCompileWithValidatedRequirements(): void
    {
        $compiler = $this->buildCompiler();

        $this->assertInstanceOf(FeaturesRequirementCompiler::class, $compiler->addValidator(
            new class () implements ValidatorInterface {
                public function __invoke(Set $requirements): void
                {
                    $requirements->validate('world');
                }
            }
        ));

        $reqs = ['hello', 'world'];
        $this->assertInstanceOf(FeaturesRequirementCompiler::class, $compiler->compile(
            $reqs,
            $this->createStub(CompiledDeploymentInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    public function testCompileWithEmptyRequirements(): void
    {
        $compiler = $this->buildCompiler();

        $reqs = [];
        $this->assertInstanceOf(FeaturesRequirementCompiler::class, $compiler->compile(
            $reqs,
            $this->createStub(CompiledDeploymentInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }

    public function testCompileWithoutValidatedRequirements(): void
    {
        $compiler = $this->buildCompiler();

        $reqs = ['hello', 'world'];

        $this->expectException(DomainException::class);
        $this->assertInstanceOf(FeaturesRequirementCompiler::class, $compiler->compile(
            $reqs,
            $this->createStub(CompiledDeploymentInterface::class),
            $this->createStub(JobWorkspaceInterface::class),
            $this->createStub(JobUnitInterface::class),
            $this->createStub(ResourceManager::class),
            $this->createStub(DefaultsBag::class),
        ));
    }
}
