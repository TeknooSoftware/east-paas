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

namespace Teknoo\East\Paas\Compilation\Conductor;

use Closure;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
        return function (CompiledDeploymentInterface $compiledDeployment, ?string $storageIdentifier): void {
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
                        $storageIdentifier
                    ) {
                        $compiler->compile(
                            $configuration,
                            $compiledDeployment,
                            $workspace,
                            $job,
                            $storageIdentifier
                        );
                    }
                );
            }
        };
    }
}
