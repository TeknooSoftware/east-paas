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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;

use Closure;
use Maclof\Kubernetes\Client as KubernetesClient;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;
use Throwable;

/**
 * State for the class Driver for the daughter instance present into the workplan
 *
 * @mixin Driver
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getClient(): Closure
    {
        return function (): KubernetesClient {
            return $this->client ?? ($this->clientFactory)(
                (string) $this->master,
                $this->credentials
            );
        };
    }

    private function runTranscriber(): Closure
    {
        return function (
            CompiledDeploymentInterface $compiledDeployment,
            PromiseInterface $mainPromise,
            bool $runDeployment,
            bool $runExposing
        ): void {
            $client = $this->getClient();

            try {
                $promise = new Promise(
                    $mainPromise->success(...),
                    static function (Throwable $error) {
                        //To break the foreach loop
                        throw $error;
                    }
                );

                foreach ($this->transcribers as $transcriber) {
                    if (
                        ($runDeployment && $transcriber instanceof DeploymentInterface)
                        || ($runExposing && $transcriber instanceof ExposingInterface)
                    ) {
                        /** @var \Teknoo\Recipe\Promise\Promise<array<string, mixed>, mixed, mixed> $promise */
                        $transcriber->transcribe($compiledDeployment, $client, $promise);
                    }
                }
            } catch (Throwable $error) {
                $mainPromise->fail($error);
            }
        };
    }
}
