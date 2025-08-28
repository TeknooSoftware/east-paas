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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\ImageCompiler;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuildableInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ImageCompiler::class)]
class ImageCompilerTest extends TestCase
{
    public function buildCompiler(): ImageCompiler
    {
        return new ImageCompiler(
            [
                'php-react-74' => [
                    'build-name' => 'php-react',
                    'tag' => '7.4',
                    'path' => '/library/php-react/7.4/',
                ],
                'php-fpm-74' => [
                    'build-name' => 'php-fpm',
                    'tag' => '7.4',
                    'path' => '/library/php-fpm/7.4/',
                ],
            ]
        );
    }

    private function getDefinitionsArray(): array
    {
        return [
            'php-fpm-74' => [
                'foo' => [
                    'bar' => 'foo'
                ]
            ],
            'foo' => [
                'build-name' => 'regisry/foo',
                'tag' => 'latest',
                'path' => '/images/bar'
            ],
        ];
    }

    public function testCompileWithoutDefinitions(): void
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->never())->method('addBuildable');

        $this->assertInstanceOf(ImageCompiler::class, $this->buildCompiler()->compile(
            $definitions,
            $compiledDeployment,
            $this->createMock(JobWorkspaceInterface::class),
            $this->createMock(JobUnitInterface::class),
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        ));
    }

    public function testCompile(): void
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects($this->exactly(2))
            ->method('addBuildable')
            ->willReturnCallback(function (BuildableInterface $buildable) use ($compiledDeployment): MockObject {
                if ('foo' === $buildable->getName()) {
                    $this->assertEquals('/foo/images/bar', $buildable->getPath());
                } else {
                    $this->assertEquals('/library/php-fpm/7.4/', $buildable->getPath());
                }

                return $compiledDeployment;
            });

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $workspace->method('runInRepositoryPath')->willReturnCallback(
            static function (callable $callback) use ($workspace): MockObject {
                $callback('/foo');
                return $workspace;
            }
        );

        $jobUnit = $this->createMock(JobUnitInterface::class);

        $this->assertInstanceOf(ImageCompiler::class, $builder->compile(
            $definitions,
            $compiledDeployment,
            $workspace,
            $jobUnit,
            $this->createMock(ResourceManager::class),
            $this->createMock(DefaultsBag::class),
        ));
    }
}
