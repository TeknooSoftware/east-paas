<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\East\Paas\Compilation;

use RuntimeException;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Compilation\Conductor\Generator;
use Teknoo\East\Paas\Compilation\Conductor\Running;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompilerInterface;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Parser\ArrayTrait;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Parser\YamlTrait;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Parser\YamlValidator;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;
use Throwable;

use function str_replace;

/**
 * Class to validate and prepare a deployment by compiling instructions from paas.yaml to objects understable by
 * deployments adapters and clusters's drivers, grouped into a summary object implemented via 'CompiledDeployment'.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Conductor implements ConductorInterface, ProxyInterface, AutomatedInterface
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

    private JobUnitInterface $job;

    private JobWorkspaceInterface $workspace;

    /**
     * @var array<string, mixed>
     */
    private array $configuration;

    /**
     * @param array<string, CompilerInterface> $compilers
     */
    public function __construct(
        private CompiledDeploymentFactoryInterface $factory,
        PropertyAccessorInterface $propertyAccessor,
        YamlParserInterface $parser,
        private YamlValidator $validator,
        private iterable $compilers,
        private ?string $storageIdentifier
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
            $validatorPromise = new Promise(
                function ($result) use ($promise) {
                    $this->getJob()->updateVariablesIn(
                        $result,
                        new Promise(
                            function ($result) use ($promise) {
                                $this->configuration = $result;

                                $promise->success($result);
                            },
                            [$promise, 'fail']
                        )
                    );
                },
                [$promise, 'fail']
            );

            $this->parseYaml(
                $configuration,
                new Promise(
                    function ($result) use ($validatorPromise) {
                        $this->validator->validate($result, $this->factory->getSchema(), $validatorPromise);
                    },
                    [$promise, 'fail']
                )
            );
        } catch (Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function compileDeployment(PromiseInterface $promise, ?string $storageIdentifier = null): ConductorInterface
    {
        $this->extract(
            $this->configuration,
            static::CONFIG_PAAS,
            [
                static::CONFIG_KEY_VERSION => 'v1',
                static::CONFIG_KEY_NAMESPACE => 'default',
            ],
            function ($paas) use ($promise, $storageIdentifier): void {
                if (!isset($paas[static::CONFIG_KEY_VERSION]) || 'v1' !== $paas[static::CONFIG_KEY_VERSION]) {
                    $promise->fail(new RuntimeException('Paas config file version not supported'));

                    return;
                }

                $version = (int) str_replace('v', '', $paas[static::CONFIG_KEY_VERSION]);
                $namespace = $paas[static::CONFIG_KEY_NAMESPACE] ?? 'default';

                try {
                    $compiledDeployment = $this->factory->build($version, $namespace);

                    $this->extractAndCompile($compiledDeployment, $storageIdentifier ?? $this->storageIdentifier);

                    $promise->success($compiledDeployment);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
