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

namespace Teknoo\East\Paas\Conductor;

use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Conductor\Compilation\HookTrait;
use Teknoo\East\Paas\Conductor\Compilation\ImageTrait;
use Teknoo\East\Paas\Conductor\Compilation\PodTrait;
use Teknoo\East\Paas\Conductor\Compilation\ServiceTrait;
use Teknoo\East\Paas\Conductor\Compilation\VolumeTrait;
use Teknoo\East\Paas\Conductor\Conductor\Generator;
use Teknoo\East\Paas\Conductor\Conductor\Running;
use Teknoo\East\Paas\Contracts\Conductor\ConductorInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Configuration\PropertyAccessorInterface;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Parser\ArrayTrait;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Parser\YamlTrait;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Conductor implements ConductorInterface, ProxyInterface, AutomatedInterface
{
    use YamlTrait;
    use ArrayTrait;
    use ImageTrait;
    use VolumeTrait;
    use HookTrait;
    use PodTrait;
    use ServiceTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private const CONFIG_PAAS = '[paas]';
    private const CONFIG_VOLUMES = '[volumes]';
    private const CONFIG_IMAGES = '[images]';
    private const CONFIG_BUILDS = '[builds]';
    private const CONFIG_PODS = '[pods]';
    private const CONFIG_SERVICES = '[services]';

    private JobUnitInterface $job;

    private JobWorkspaceInterface $workspace;

    private string $path;

    /**
     * @var array<string, mixed>
     */
    private array $configuration;

    /**
     * @var array<string, string|array<string, mixed>>
     */
    private iterable $imagesLibrary;

    /**
     * @var iterable<string, HookInterface>
     */
    private iterable $hooksLibrary;

    /**
     * @param iterable<string, string|array<string, mixed>> $imagesLibrary
     * @param iterable<string, HookInterface> $hooksLibrary
     */
    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        YamlParserInterface $parser,
        iterable $imagesLibrary,
        iterable $hooksLibrary
    ) {
        $this->setPropertyAccessor($propertyAccessor);
        $this->setParser($parser);
        $this->imagesLibrary = $imagesLibrary;
        $this->hooksLibrary = $hooksLibrary;

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
            $this->parseYaml(
                $configuration,
                new Promise(
                    function ($result) use ($promise) {
                        $this->getJob()->updateVariablesIn(
                            $result,
                            new Promise(
                                function ($result) use ($promise) {
                                    $this->configuration = $result;

                                    $promise->success($result);
                                },
                                static function (\Throwable $error) use ($promise) {
                                    $promise->fail($error);
                                }
                            )
                        );
                    },
                    static function (\Throwable $error) use ($promise) {
                        $promise->fail($error);
                    }
                )
            );
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function compileDeployment(PromiseInterface $promise): ConductorInterface
    {
        $this->extract(
            $this->configuration,
            static::CONFIG_PAAS,
            [
                'version' => 'v1',
            ],
            function ($paas) use ($promise): void {
                if (!isset($paas['version']) || 'v1' !== $paas['version']) {
                    $promise->fail(new \RuntimeException('Paas config file version not supported'));

                    return;
                }

                try {
                    $compiledDeployment = new CompiledDeployment();

                    $this->extractAndCompile($compiledDeployment);

                    $promise->success($compiledDeployment);
                } catch (\Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
