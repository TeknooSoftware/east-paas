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

namespace Teknoo\Tests\East\Paas\Conductor\Compilation;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Conductor\Compilation\ImageCompiler;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Container\BuildableInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Conductor\Compilation\ImageCompiler
 */
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

    public function testCompileWithoutDefinitions()
    {
        $definitions = [];

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::never())->method('addBuildable');

        self::assertInstanceOf(
            ImageCompiler::class,
            $this->buildCompiler()->compile(
                $definitions,
                $compiledDeployment,
                $this->createMock(JobWorkspaceInterface::class),
                $this->createMock(JobUnitInterface::class )
            )
        );
    }

    public function testCompile()
    {
        $definitions = $this->getDefinitionsArray();
        $builder = $this->buildCompiler();

        $compiledDeployment = $this->createMock(CompiledDeploymentInterface::class);
        $compiledDeployment->expects(self::exactly(2))
            ->method('addBuildable')
            ->willReturnCallback(function (BuildableInterface $buildable) use ($compiledDeployment) {
                if ('foo' === $buildable->getName()) {
                    self::assertEquals('/foo/images/bar', $buildable->getPath());
                } else {
                    self::assertEquals('/library/php-fpm/7.4/', $buildable->getPath());
                }

                return $compiledDeployment;
            });

        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $workspace->expects(self::any())->method('runInRoot')->willReturnCallback(
            static function (callable $callback) use ($workspace) {
                $callback('/foo');
                return $workspace;
            }
        );

        $jobUnit = $this->createMock(JobUnitInterface::class );

        self::assertInstanceOf(
            ImageCompiler::class,
            $builder->compile(
                $definitions,
                $compiledDeployment,
                $workspace,
                $jobUnit
            )
        );
    }
}
