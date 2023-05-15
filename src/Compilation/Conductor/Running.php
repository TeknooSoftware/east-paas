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

namespace Teknoo\East\Paas\Compilation\Conductor;

use Closure;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Compilation\Conductor;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getJob(): Closure
    {
        return fn(): JobUnitInterface => $this->job;
    }

    private function getWorkspace(): Closure
    {
        return fn(): JobWorkspaceInterface => $this->workspace;
    }

    private function extractAndCompile(): Closure
    {
        return function (
            CompiledDeploymentInterface $compiledDeployment,
            ?string $storageIdentifier,
            ?string $storageSize,
            ?string $ociRegistryConfig = null,
        ): void {
            $workspace = $this->getWorkspace();
            $job = $this->getJob();

            foreach ($this->compilers as $pattern => $compiler) {
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
                        $storageIdentifier,
                        $storageSize,
                        $ociRegistryConfig,
                    ): void {
                        $compiler->compile(
                            $configuration,
                            $compiledDeployment,
                            $workspace,
                            $job,
                            $storageIdentifier,
                            $storageSize,
                            $ociRegistryConfig,
                        );
                    }
                );
            }
        };
    }
}
