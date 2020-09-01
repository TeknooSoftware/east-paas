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

namespace Teknoo\Tests\East\Paas\Conductor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Conductor\Conductor;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Conductor\Conductor
 * @covers \Teknoo\East\Paas\Conductor\Conductor\Generator
 * @covers \Teknoo\East\Paas\Conductor\Conductor\Running
 * @covers \Teknoo\East\Paas\Conductor\Compilation\HookTrait
 * @covers \Teknoo\East\Paas\Conductor\Compilation\ImageTrait
 * @covers \Teknoo\East\Paas\Conductor\Compilation\VolumeTrait
 * @covers \Teknoo\East\Paas\Conductor\Compilation\ServiceTrait
 * @covers \Teknoo\East\Paas\Conductor\Compilation\PodTrait
 * @covers \Teknoo\East\Paas\Parser\ArrayTrait
 * @covers \Teknoo\East\Paas\Parser\YamlTrait
 */
class ConductorTest extends TestCase
{
    private ?PropertyAccessorInterface $propertyAccessor = null;

    private ?YamlParserInterface $parser = null;

    /**
     * @return MockObject|PropertyAccessorInterface
     */
    public function getPropertyAccessorMock(): PropertyAccessorInterface
    {
        if (!$this->propertyAccessor instanceof PropertyAccessorInterface) {
            $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        }

        return $this->propertyAccessor;
    }

    /**
     * @return MockObject|YamlParserInterface
     */
    public function getYamlParser(): YamlParserInterface
    {
        if (!$this->parser instanceof YamlParserInterface) {
            $this->parser = $this->createMock(YamlParserInterface::class);
        }

        return $this->parser;
    }

    public function buildConductor(): Conductor
    {
        return new Conductor(
            $this->getPropertyAccessorMock(),
            $this->getYamlParser(),
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
            ],
            [
                'composer' => $this->createMock(HookInterface::class)
            ]
        );
    }

    public function testConfigureBadJobUnit()
    {
        $this->expectException(\TypeError::class);

        $this->buildConductor()->configure(
            new \stdClass(),
            $this->createMock(JobWorkspaceInterface::class)
        );
    }

    public function testConfigureBadJobWorkspace()
    {
        $this->expectException(\TypeError::class);

        $this->buildConductor()->configure(
            $this->createMock(JobUnitInterface::class),
            new \stdClass()
        );
    }

    public function testConfigure()
    {
        $conductor = $this->buildConductor();
        $newConductor = $conductor->configure(
            $this->createMock(JobUnitInterface::class),
            $this->createMock(JobWorkspaceInterface::class)
        );

        self::assertInstanceOf(
            Conductor::class,
            $newConductor
        );

        self::assertNotSame(
            $conductor,
            $newConductor
        );
    }

    public function testPrepareBadPath()
    {
        $this->expectException(\TypeError::class);

        $this->buildConductor()->configure(
            new \stdClass(),
            'foo',
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testPrepareBadConfiguration()
    {
        $this->expectException(\TypeError::class);

        $this->buildConductor()->configure(
            'foo',
            new \stdClass(),
            $this->createMock(PromiseInterface::class)
        );
    }

    public function testPrepareBadPromise()
    {
        $this->expectException(\TypeError::class);

        $this->buildConductor()->configure(
            'foo',
            'bar',
            new \stdClass()
        );
    }

    private function getResultArray(): array
    {
        return [
            'paas' => ['version' => 'v1'],
            'images' => [
                'foo' => [
                    'build-name' => 'foo',
                    'tag' => 'lastest',
                    'path' => '/images/${FOO}',
                ],
            ],
            'builds' => [
                'composer-init' => [
                    'composer' => '${COMPOSER}',
                ],
            ],
            'volumes' => [
                'main' => [
                    'target' => '/opt/paas/',
                    'add' => [
                        'src',
                        'vendor',
                        'composer.json',
                        'composer.lock',
                        'composer.phar',
                    ],
                ],
            ],
            'pods' => [
                'php-pod' => [
                    'replicas' => 1,
                    'containers' => [
                        'php-react' => [
                            'replicas' => 3,
                            'image' => 'php-react',
                            'version' => 7.4,
                            'listen' => [8080]
                        ],
                    ],
                ],
            ],
            'services' => [
                'php-react' => [
                    [
                        'listen' => 80,
                        'target' => 8080,
                    ],
                ],
            ],
        ];
    }

    public function testPrepare()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $path = '/foo/bar';

        $result = $this->getResultArray();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects(self::any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects(self::once())
            ->method('updateVariablesIn')
            ->with($result)
            ->willReturnCallback(
                function (array $result, PromiseInterface $promise) use ($jobUnit) {
                    $result['image']['foo']['path'] = '/image/foo';
                    $promise->success($result);

                    return $jobUnit;
                }
            );

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->prepare(
                $yaml,
                $promise
            )
        );
    }

    public function testPrepareErrorInYamlParse()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $path = '/foo/bar';

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects(self::any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) {
                    $promise->fail(new \RuntimeException('foo'));

                    return $this->getYamlParser();
                }
            );

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->prepare(
                $yaml,
                $promise
            )
        );
    }

    public function testPrepareOnErrorInUpdatingVariables()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $path = '/foo/bar';

        $result = $this->getResultArray();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())->method('success');
        $promise->expects(self::once())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects(self::any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects(self::once())
            ->method('updateVariablesIn')
            ->with($result)
            ->willReturnCallback(
                function (array $result, PromiseInterface $promise) use ($jobUnit) {
                    $promise->fail(new \DomainException('foo'));

                    return $jobUnit;
                }
            );

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->prepare(
                $yaml,
                $promise
            )
        );
    }

    public function testCompileDeploymentBadCallback()
    {
        $this->expectException(\TypeError::class);

        $this->buildConductor()->compileDeployment(
            new \stdClass()
        );
    }

    public function testCompileDeploymentWithUnsupportedVersion()
    {
        $yaml = <<<'EOF'
paas:
  version: v2
/*...*/
EOF;

        $result = $this->getResultArray();
        $result['paas']['version'] = 'v2';

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);
        $this->getYamlParser()
            ->expects(self::any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $this->getPropertyAccessorMock()
            ->expects(self::any())
            ->method('getValue')
            ->willReturnCallback(
                function (array $array, string $propertyPath, callable $callback, $default = null) {
                    $pa = new SymfonyPropertyAccessor();

                    if ($pa->isReadable($array, $propertyPath)) {
                        $callback($pa->getValue($array, $propertyPath));

                        return $this->getPropertyAccessorMock();
                    }

                    if (null !== $default) {
                        $callback($default);
                    }

                    return $this->getPropertyAccessorMock();
                }
            );

        $jobUnit->expects(self::once())
            ->method('updateVariablesIn')
            ->with($result)
            ->willReturnCallback(
                function (array $result, PromiseInterface $promise) use ($jobUnit) {
                    $promise->success($result);

                    return $jobUnit;
                }
            );

        $conductor->prepare(
            $yaml,
            $promise
        );

        $promise2 = $this->createMock(PromiseInterface::class);
        $promise2->expects(self::never())->method('success');
        $promise2->expects(self::once())->method('fail');

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->compileDeployment($promise2)
        );
    }

    public function testCompileDeployment()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $result = $this->getResultArray();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);
        $this->getYamlParser()
            ->expects(self::any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $this->getPropertyAccessorMock()
            ->expects(self::any())
            ->method('getValue')
            ->willReturnCallback(
                function (array $array, string $propertyPath, callable $callback, $default = null) {
                    $pa = new SymfonyPropertyAccessor();

                    if ($pa->isReadable($array, $propertyPath)) {
                        $callback($pa->getValue($array, $propertyPath));

                        return $this->getPropertyAccessorMock();
                    }

                    if (null !== $default) {
                        $callback($default);
                    }

                    return $this->getPropertyAccessorMock();
                }
            );

        $jobUnit->expects(self::once())
            ->method('updateVariablesIn')
            ->with($result)
            ->willReturnCallback(
                function (array $result, PromiseInterface $promise) use ($jobUnit) {
                    $promise->success($result);

                    return $jobUnit;
                }
            );

        $conductor->prepare(
            $yaml,
            $promise
        );

        $out = null;
        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->compileDeployment(new Promise(function ($cd) use (&$out) {
                $out = $cd;

                self::assertInstanceOf(CompiledDeployment::class, $cd);
            }))
        );

        self::assertNotNull($out);
    }

    public function testCompileDeploymentWithUnavailableHook()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $result = $this->getResultArray();
        $result['builds']['fooo'] = ['bar' => true];

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('success');
        $promise->expects(self::never())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);
        $this->getYamlParser()
            ->expects(self::any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $this->getPropertyAccessorMock()
            ->expects(self::any())
            ->method('getValue')
            ->willReturnCallback(
                function (array $array, string $propertyPath, callable $callback, $default = null) {
                    $pa = new SymfonyPropertyAccessor();

                    if ($pa->isReadable($array, $propertyPath)) {
                        $callback($pa->getValue($array, $propertyPath));

                        return $this->getPropertyAccessorMock();
                    }

                    if (null !== $default) {
                        $callback($default);
                    }

                    return $this->getPropertyAccessorMock();
                }
            );

        $jobUnit->expects(self::once())
            ->method('updateVariablesIn')
            ->with($result)
            ->willReturnCallback(
                function (array $result, PromiseInterface $promise) use ($jobUnit) {
                    $promise->success($result);

                    return $jobUnit;
                }
            );

        $conductor->prepare(
            $yaml,
            $promise
        );

        $promise2 = $this->createMock(PromiseInterface::class);
        $promise2->expects(self::never())->method('success');
        $promise2->expects(self::once())->method('fail');

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->compileDeployment($promise2)
        );
    }
}