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

namespace Teknoo\East\Paas\Compilation\Conductor;

use Closure;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\ResourceManager;
use Teknoo\East\Paas\Compilation\Conductor;
use Teknoo\East\Paas\Compilation\Exception\UnsupportedVersion;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State for the class Conductor for the daughter instance, to prepare a deployment by compiling instructions from
 * paas.yaml to objects understable by deployments adapters and clusters's drivers, grouped into a
 * summary object implemented via 'CompiledDeployment'.
 *
 * @mixin Conductor
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getJob(): Closure
    {
        return function (): JobUnitInterface {
            return $this->job;
        };
    }

    private function getWorkspace(): Closure
    {
        return function (): JobWorkspaceInterface {
            return $this->workspace;
        };
    }

    private function extractAndCompile(): Closure
    {
        return function (
            CompiledDeploymentInterface $compiledDeployment,
        ): void {
            $workspace = $this->getWorkspace();
            $job = $this->getJob();

            $resourceManager = new ResourceManager();
            $job->prepareQuotas(
                $this->quotaFactory,
                new Promise(
                    onSuccess: function (array $capacities) use ($resourceManager): void {
                        foreach ($capacities as $name => $capacity) {
                            $resourceManager->updateQuotaAvailability($name, $capacity);
                        }
                    }
                )
            );

            $defaultsBags = new DefaultsBag();
            $compiledDeployment->setDefaultBags($defaultsBags);

            $version = 'v' . $compiledDeployment->getVersion();
            $compilers = $this->compilers[$version] ?? [];

            /** @var CompilerInterface $compiler */
            foreach ($compilers as $pattern => $compiler) {
                $this->extract(
                    $this->configuration,
                    $pattern,
                    [],
                    static function (
                        $configuration
                    ) use (
                        $compiledDeployment,
                        $compiler,
                        $workspace,
                        $job,
                        $resourceManager,
                        $defaultsBags,
                    ): void {
                        $compiler->compile(
                            definitions: $configuration,
                            compiledDeployment: $compiledDeployment,
                            workspace: $workspace,
                            job: $job,
                            resourceManager: $resourceManager,
                            defaultsBag: $defaultsBags,
                        );
                    }
                );
            }

            $resourceManager->computeAutomaticReservations();
        };
    }
}
