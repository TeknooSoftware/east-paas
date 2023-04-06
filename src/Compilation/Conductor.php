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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation;

use RuntimeException;
use Teknoo\East\Paas\Contracts\Compilation\ExtenderInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Compilation\Conductor\Generator;
use Teknoo\East\Paas\Compilation\Conductor\Running;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Parser\ArrayTrait;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Parser\YamlTrait;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Parser\YamlValidator;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;
use Throwable;

use function str_replace;

/**
 * Class to validate and prepare a deployment by compiling instructions from paas.yaml to objects understable by
 * deployments adapters and clusters's drivers, grouped into a summary object implemented via 'CompiledDeployment'.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Conductor implements ConductorInterface, AutomatedInterface
{
    use YamlTrait;
    use ArrayTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private const CONFIG_PAAS = '[paas]';
    private const CONFIG_KEY_VERSION = 'version';
    private const CONFIG_KEY_NAMESPACE = 'namespace';
    private const CONFIG_KEY_HNC = 'hierarchical-namespaces';
    private const CONFIG_KEY_PREFIX = 'prefix';

    private const CONFIG_DEFAULTS = '[defaults]';
    private const CONFIG_KEY_STORAGE_PROVIDER = 'storage-provider';
    private const CONFIG_KEY_STORAGE_SIZE = 'storage-size';
    private const CONFIG_KEY_OCI_REGISTRY_CONFIG_NAME = 'oci-registry-config-name';

    private JobUnitInterface $job;

    private JobWorkspaceInterface $workspace;

    /**
     * @var array<string, mixed>
     */
    private array $configuration = [];

    /**
     * @param array<string, CompilerInterface> $compilers
     */
    public function __construct(
        private readonly CompiledDeploymentFactoryInterface $factory,
        PropertyAccessorInterface $propertyAccessor,
        YamlParserInterface $parser,
        private readonly YamlValidator $validator,
        private readonly iterable $compilers,
        private readonly ?string $storageIdentifier,
        private readonly ?string $storageSize,
        private readonly ?string $defaultOciRegistryConfig,
    ) {
        $this->setPropertyAccessor($propertyAccessor);
        $this->setParser($parser);

        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
            Generator::class,
            Running::class,
        ];
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property(Running::class))
                ->with('job', new Property\IsNotEmpty())
                ->with('workspace', new Property\IsNotEmpty()),

            (new Property(Generator::class))
                ->with('job', new Property\IsEmpty()),
            (new Property(Generator::class))
                ->with('workspace', new Property\IsEmpty()),
        ];
    }

    public function configure(JobUnitInterface $job, JobWorkspaceInterface $workspace): ConductorInterface
    {
        $that = clone $this;
        $that->job = $job;
        $that->workspace = $workspace;

        $that->updateStates();

        return $that;
    }

    public function prepare(string $configuration, PromiseInterface $promise): ConductorInterface
    {
        try {
            /** @var Promise<array<string, mixed>, mixed, array<string, mixed>> $configuredPromise */
            $configuredPromise = new Promise(
                function ($result, PromiseInterface $next): void {
                    $this->configuration = $result;

                    $next->success($result);
                },
                allowNext: true
            );

            /** @var Promise<array<int|string, mixed>, mixed, array<string, mixed>> $validatedPromise */
            $validatedPromise = new Promise(
                fn ($result, PromiseInterface $next) => $this->getJob()->updateVariablesIn(
                    $result,
                    $next
                ),
                allowNext: true
            );

            $parsedPromise = new Promise(
                function (array $result): array {
                    foreach ($this->compilers as $pattern => $compiler) {
                        if (!$compiler instanceof ExtenderInterface) {
                            continue;
                        }

                        $this->extract(
                            $result,
                            $pattern,
                            [],
                            function (
                                $configuration
                            ) use (
                                $compiler,
                                &$result,
                                $pattern,
                            ): void {
                                $compiler->extends($configuration);

                                $this->replace($result, $pattern, $configuration);
                            }
                        );
                    }

                    return $result;
                },
                allowNext: true
            );

            /**
             * @var Promise<
             *     array<mixed, mixed>,
             *     mixed,
             *     PromiseInterface<array<int|string, mixed>, mixed>
             * > $extendedPromise
             */
            $extendedPromise = new Promise(
                fn ($result, PromiseInterface $next): YamlValidator => $this->validator->validate(
                    $result,
                    $this->factory->getSchema(),
                    $next
                ),
                allowNext: true
            );

            $this->parseYaml(
                $configuration,
                $parsedPromise
                    ->next($extendedPromise, autoCall: true)
                    ->next($validatedPromise, autoCall: true)
                    ->next($configuredPromise, autoCall: true)
                    ->next($promise, autoCall: true)
            );
        } catch (Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function compileDeployment(
        PromiseInterface $promise,
        ?string $storageIdentifier = null,
        ?string $storageSize = null,
        ?string $ociRegistryConfig = null,
    ): ConductorInterface {

        $this->extract(
            $this->configuration,
            self::CONFIG_DEFAULTS,
            [
                self::CONFIG_KEY_STORAGE_PROVIDER => $storageIdentifier,
                self::CONFIG_KEY_STORAGE_SIZE => $storageSize,
                self::CONFIG_KEY_OCI_REGISTRY_CONFIG_NAME => $ociRegistryConfig ?? $this->defaultOciRegistryConfig,
            ],
            function ($defaults) use ($promise): void {
                $storageIdentifier = $defaults[self::CONFIG_KEY_STORAGE_PROVIDER] ?? null;
                $storageSize = $defaults[self::CONFIG_KEY_STORAGE_SIZE] ?? null;
                $ociRegistryConfig = $defaults[self::CONFIG_KEY_OCI_REGISTRY_CONFIG_NAME] ?? null;

                $this->extract(
                    $this->configuration,
                    self::CONFIG_PAAS,
                    [
                        self::CONFIG_KEY_VERSION => 'v1',
                        self::CONFIG_KEY_NAMESPACE => 'default',
                        self::CONFIG_KEY_PREFIX => null,
                    ],
                    function ($paas) use ($promise, $storageIdentifier, $storageSize, $ociRegistryConfig): void {
                        if (!isset($paas[self::CONFIG_KEY_VERSION]) || 'v1' !== $paas[self::CONFIG_KEY_VERSION]) {
                            $promise->fail(new RuntimeException('Paas config file version not supported', 400));

                            return;
                        }

                        $version = (int) str_replace('v', '', $paas[self::CONFIG_KEY_VERSION]);
                        $namespace = $paas[self::CONFIG_KEY_NAMESPACE] ?? 'default';
                        $hierarchicalNamespaces = $paas[self::CONFIG_KEY_HNC] ?? false;
                        $prefix = $paas[self::CONFIG_KEY_PREFIX] ?? null;

                        try {
                            $compiledDeployment = $this->factory->build(
                                version: $version,
                                namespace: $namespace,
                                hierarchicalNamespaces: !empty($hierarchicalNamespaces),
                                prefix: $prefix,
                            );

                            $this->extractAndCompile(
                                $compiledDeployment,
                                $storageIdentifier ?? $this->storageIdentifier,
                                $storageSize ?? $this->storageSize,
                                $ociRegistryConfig ?? $this->defaultOciRegistryConfig,
                            );

                            $promise->success($compiledDeployment);
                        } catch (Throwable $error) {
                            $promise->fail($error);
                        }
                    }
                );
            },
        );

        return $this;
    }
}
