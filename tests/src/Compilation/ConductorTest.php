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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Compilation;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Symfony\Component\PropertyAccess\PropertyAccessor as SymfonyPropertyAccessor;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as QuotaFactory;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as ResourceFactory;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Compilation\Conductor;
use Teknoo\East\Paas\Compilation\Conductor\Generator;
use Teknoo\East\Paas\Compilation\Conductor\Running;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Compilation\ExtenderInterface;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Parser\YamlValidator;
use Teknoo\Recipe\Promise\PromiseInterface;
use TypeError;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Running::class)]
#[CoversClass(Generator::class)]
#[CoversClass(Conductor::class)]
class ConductorTest extends TestCase
{
    private ?CompiledDeploymentFactoryInterface $factory = null;

    private ?PropertyAccessorInterface $propertyAccessor = null;

    private ?YamlParserInterface $parser = null;

    private ?YamlValidator $validator = null;

    private ?ResourceFactory $resourceFactory = null;

    /**
     * @return MockObject|CompiledDeploymentFactoryInterface
     */
    public function getCompiledDeploymentFactory(): CompiledDeploymentFactoryInterface
    {
        if (!$this->factory instanceof CompiledDeploymentFactoryInterface) {
            $this->factory = $this->createMock(CompiledDeploymentFactoryInterface::class);

            $cd = $this->createMock(CompiledDeploymentInterface::class);
            $cd->expects($this->any())
                ->method('getVersion')
                ->willReturn(1.0);

            $this->factory->expects($this->any())
                ->method('build')
                ->willReturn($cd);

            $this->factory->expects($this->any())
                ->method('getSchema')
                ->willReturn('fooBar');
        }

        return $this->factory;
    }

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

    /**
     * @return MockObject|YamlValidator
     */
    public function getYamlValidator(): YamlValidator
    {
        if (!$this->validator instanceof YamlValidator) {
            $this->validator = $this->createMock(YamlValidator::class);
        }

        return $this->validator;
    }

    /**
     * @return MockObject|ResourceFactory
     */
    public function getResourceFactory(): ResourceFactory
    {
        if (!$this->resourceFactory instanceof YamlValidator) {
            $this->resourceFactory = $this->createMock(ResourceFactory::class);
        }

        return $this->resourceFactory;
    }

    public function buildConductor(
        array $compilers = []
    ): Conductor {
        if (empty($compilers)) {
            $compilers = [
                '[secrets]' => $this->createMock(CompilerInterface::class),
                '[services]' => new class implements CompilerInterface, ExtenderInterface {
                    public function compile(
                        array &$definitions,
                        CompiledDeploymentInterface $compiledDeployment,
                        JobWorkspaceInterface $workspace,
                        JobUnitInterface $job,
                        ResourceManager $resourceManager,
                        DefaultsBag $defaultsBag,
                    ): CompilerInterface {
                        return $this;
                    }

                    public function extends(
                        array &$definitions,
                    ): ExtenderInterface {
                        $definitions['foo'] = 'bar';
                        return $this;
                    }
                },
                '[volumes]' => $this->createMock(CompilerInterface::class),
            ];
        }

        return new Conductor(
            $this->getCompiledDeploymentFactory(),
            $this->getPropertyAccessorMock(),
            $this->getYamlParser(),
            $this->getYamlValidator(),
            $this->getResourceFactory(),
            [
                'v1' => $compilers,
                'v1.1' => $compilers,
            ],
        );
    }

    public function testConfigureBadJobUnit()
    {
        $this->expectException(TypeError::class);

        $this->buildConductor()->configure(
            new stdClass(),
            $this->createMock(JobWorkspaceInterface::class)
        );
    }

    public function testConfigureBadJobWorkspace()
    {
        $this->expectException(TypeError::class);

        $this->buildConductor()->configure(
            $this->createMock(JobUnitInterface::class),
            new stdClass()
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
        $this->expectException(TypeError::class);

        $this->buildConductor()->configure(
            new stdClass(),
            $this->createMock(JobWorkspaceInterface::class)
        );
    }

    public function testPrepareBadConfiguration()
    {
        $this->expectException(TypeError::class);

        $this->buildConductor()->configure(
            $this->createMock(JobUnitInterface::class),
            new stdClass(),
        );
    }

    private function getResultArray(): array
    {
        return [
            'paas' => ['version' => 'v1'],
            'images' => [
                'php-fpm-74' => [
                    'foo' => [
                        'bar' => 'foo'
                    ]
                ],
                'foo' => [
                    'build-name' => 'regisry/foo',
                    'tag' => 'latest',
                    'path' => '/images/${FOO}'
                ],
            ],
            'secrets' => [
                'demo_vault' => [
                    'provider' => 'hashicorp/vault',
                    'options' => [
                        'server' => 'vault.teknoo.software',
                    ],
                ],
                'map_vault' => [
                    'provider' => 'map',
                    'options' => [
                        'key1' =>  'value1',
                        'key2' =>  'foo',
                    ]
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
                        'php-composer' => [
                            'replicas' => 3,
                            'image' => 'registry/lib/php-composer',
                            'variables' => [
                                'from-secrets' => [
                                    'bar' => 'myvauult.key',
                                ],
                                'foo' => 'bar'
                            ],
                            'version' => 7.4,
                            'volumes' => [
                                'persistent_volume' => [
                                    'persistent' => true,
                                    'mount-path' => '/app/persistent/',
                                ],
                                'embedded' => [
                                    'add' => [
                                      'foo',
                                      'bar',
                                    ],
                                    'mount-path' => '/app/embedded/',
                                ],
                                'other_name2' => [
                                    'from' => 'main',
                                    'mount-path' => '/app/vendor/',
                                ],
                                'vault' => [
                                    'from-secret' => 'vault',
                                    'mount-path' => '/app/vendor/',
                                ],
                            ]
                        ],
                    ],
                ],
            ],
            'services' => [
                'php-react' => [
                    'ports' => [
                        [
                            'listen' => 80,
                            'target' => 8080,
                        ],
                    ],
                ],
                'php-udp' => [
                    'pod' => 'php-react',
                    'protocol' => 'udp',
                    'ports' => [
                        [
                            'listen' => 80,
                            'target' => 8080,
                        ],
                    ],
                ],
            ],
            'ingresses' => [
                'demo' => [
                    'host' => 'demo-paas.teknoo.software',
                    'tls' => [
                        'cert' => 'foo',
                        'key' => 'bar',
                    ],
                    'service' => [
                        'name' => 'php-react',
                        'port' => 80
                    ],
                    'paths' => [
                        [
                            'path' => '/demo',
                            'service' => [
                                'name' => 'php-udp',
                                'port' => 8080,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testPrepareFromGenerator()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $result = $this->getResultArray();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $conductor = $this->buildConductor();

        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects($this->any())
            ->method('filteringConditions')
            ->willReturnCallback(
                function (array $configuration, PromiseInterface $promise)  use ($jobUnit) {
                    $promise->success($configuration);

                    return $jobUnit;
                }
            );

        $this->getYamlValidator()
            ->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function (array $configuration, string $xsd, PromiseInterface $promise) {
                    $promise->success($configuration);

                    return $this->getYamlValidator();
                }
            );

        $jobUnit->expects($this->never())
            ->method('updateVariablesIn');

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->prepare(
                $yaml,
                $promise
            )
        );
    }

    public function testPrepareInV1()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $result = $this->getResultArray();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects($this->never())
            ->method('filteringConditions');

        $this->getYamlValidator()
            ->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function (array $configuration, string $xsd, PromiseInterface $promise) {
                    $promise->success($configuration);

                    return $this->getYamlValidator();
                }
            );

        $jobUnit->expects($this->once())
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

    public function testPrepareInV1dot1()
    {
        $yaml = <<<'EOF'
paas:
  version: v1.1
/*...*/
EOF;

        $result = $this->getResultArray();
        $result['paas']['version'] = 'v1.1';

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects($this->once())
            ->method('filteringConditions')
            ->willReturnCallback(
                function (array $configuration, PromiseInterface $promise)  use ($jobUnit) {
                    $promise->success($configuration);

                    return $jobUnit;
                }
            );

        $this->getYamlValidator()
            ->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function (array $configuration, string $xsd, PromiseInterface $promise) {
                    $promise->success($configuration);

                    return $this->getYamlValidator();
                }
            );

        $jobUnit->expects($this->once())
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

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) {
                    $promise->fail(new RuntimeException('foo'));

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

    public function testPrepareErrorInConditionFiltering()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) {
                    $promise->success($configuration);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects($this->any())
            ->method('filteringConditions')
            ->willReturnCallback(
                function (array $configuration, PromiseInterface $promise)  use ($jobUnit) {
                    $promise->fail(new RuntimeException('error'));

                    return $this->getYamlValidator();
                }
            );

        $this->getYamlValidator()
            ->expects($this->never())
            ->method('validate');

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->prepare(
                $yaml,
                $promise
            )
        );
    }

    public function testPrepareErrorInYamlValidation()
    {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) {
                    $promise->success($configuration);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects($this->any())
            ->method('filteringConditions')
            ->willReturnCallback(
                function (array $configuration, PromiseInterface $promise)  use ($jobUnit) {
                    $promise->success($configuration);

                    return $jobUnit;
                }
            );

        $this->getYamlValidator()
            ->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function (array $configuration, string $xsd, PromiseInterface $promise) {
                    $promise->fail(new RuntimeException('error'));

                    return $this->getYamlValidator();
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

        $result = $this->getResultArray();

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);

        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects($this->any())
            ->method('filteringConditions')
            ->willReturnCallback(
                function (array $configuration, PromiseInterface $promise)  use ($jobUnit) {
                    $promise->success($configuration);

                    return $jobUnit;
                }
            );

        $this->getYamlValidator()
            ->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function (array $configuration, string $xsd, PromiseInterface $promise) {
                    $promise->success($configuration);

                    return $this->getYamlValidator();
                }
            );

        $jobUnit->expects($this->once())
            ->method('updateVariablesIn')
            ->with($result)
            ->willReturnCallback(
                function (array $result, PromiseInterface $promise) use ($jobUnit) {
                    $promise->fail(new DomainException('foo'));

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
        $this->expectException(TypeError::class);

        $this->buildConductor()->compileDeployment(
            new stdClass()
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
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = $this->buildConductor()->configure($jobUnit, $workspace);
        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $jobUnit->expects($this->never())
            ->method('filteringConditions');

        $this->getYamlValidator()
            ->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function (array $configuration, string $xsd, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlValidator();
                }
            );

        $this->getPropertyAccessorMock()
            ->expects($this->any())
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

        $jobUnit->expects($this->never())
            ->method('updateVariablesIn');

        $conductor->prepare(
            $yaml,
            $promise
        );

        $promise2 = $this->createMock(PromiseInterface::class);
        $promise2->expects($this->never())->method('success');
        $promise2->expects($this->once())->method('fail');

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->compileDeployment($promise2)
        );
    }

    private function prepareTestForCompile(
        array $result,
        ?Conductor $conductor = null,
        array $quotas = [],
    ): Conductor {
        $yaml = <<<'EOF'
paas:
  version: v1
/*...*/
EOF;

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $jobUnit = $this->createMock(JobUnitInterface::class);
        if (!empty($quotas)) {
            $jobUnit->expects($this->once())
                ->method('prepareQuotas')
                ->willReturnCallback(
                    function (QuotaFactory $factory, PromiseInterface $promise) use ($jobUnit) {
                        $promise->success([
                            'cpu' => $this->createMock(AvailabilityInterface::class),
                            'memory' => $this->createMock(AvailabilityInterface::class),
                        ]);

                        return $jobUnit;
                    }
                );
        }

        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $conductor = ($conductor ?? $this->buildConductor())->configure($jobUnit, $workspace);

        $jobUnit->expects($this->any())
            ->method('filteringConditions')
            ->willReturnCallback(
                function (array $configuration, PromiseInterface $promise)  use ($jobUnit) {
                    $promise->success($configuration);

                    return $jobUnit;
                }
            );

        $this->getYamlParser()
            ->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                function (string $configuration, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlParser();
                }
            );

        $this->getYamlValidator()
            ->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function (array $configuration, string $xsd, PromiseInterface $promise) use ($result) {
                    $promise->success($result);

                    return $this->getYamlValidator();
                }
            );

        $this->getPropertyAccessorMock()
            ->expects($this->any())
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

        $jobUnit->expects($this->once())
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

        return $conductor;
    }

    public function testCompileDeployment()
    {
        $result = $this->getResultArray();

        $conductor = $this->prepareTestForCompile($result);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with(self::callback(fn ($x) => $x instanceof CompiledDeploymentInterface));
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->compileDeployment($promise)
        );
    }

    public function testCompileDeploymentWithQuotasInJobs()
    {
        $result = $this->getResultArray();

        $conductor = $this->prepareTestForCompile(
            result: $result,
            quotas: ['compute' => ['cpu' => 5]]
        );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with(self::callback(fn ($x) => $x instanceof CompiledDeploymentInterface));
        $promise->expects($this->never())->method('fail');

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->compileDeployment($promise)
        );
    }

    public function testCompileDeploymentErrorIntercepted()
    {
        $result = $this->getResultArray();

        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->expects($this->any())->method('compile')->willThrowException(new RuntimeException('error'));

        $conductor = new Conductor(
            $this->getCompiledDeploymentFactory(),
            $this->getPropertyAccessorMock(),
            $this->getYamlParser(),
            $this->getYamlValidator(),
            $this->getResourceFactory(),
            [
                'v1' => [
                    '[secrets]' => $compiler,
                    '[volumes]' => $compiler,
                ],
            ],
        );
        $conductor = $this->prepareTestForCompile($result, $conductor);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->once())->method('fail');

        self::assertInstanceOf(
            ConductorInterface::class,
            $conductor->compileDeployment($promise)
        );
    }
}