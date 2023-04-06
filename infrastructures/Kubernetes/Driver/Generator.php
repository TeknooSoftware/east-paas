<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;

use Closure;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Exception\GeneratorStateException;
use Teknoo\Kubernetes\Client as KubernetesClient;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State for the class Driver for the mother instance present into container, to build new Conductor instance via
 * a self cloning.
 *
 * @mixin Driver
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Generator implements StateInterface
{
    use StateTrait;

    private function runTranscriber(): Closure
    {
        return function (
            CompiledDeploymentInterface $compiledDeployment,
            PromiseInterface $mainPromise,
            bool $runDeployment,
            bool $runExposing
        ): KubernetesClient {
            throw new GeneratorStateException('Driver is in generator state');
        };
    }
}
