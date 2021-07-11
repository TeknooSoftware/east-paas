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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes\Driver;

use Closure;
use Maclof\Kubernetes\Client as KubernetesClient;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
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

            $promise = new Promise(
                [$mainPromise, 'success'],
                static function (Throwable $error) {
                    //To break the foreach loop
                    throw $error;
                }
            );

            try {
                foreach ($this->transcribers as $transcriber) {
                    if (
                        ($runDeployment && $transcriber instanceof DeploymentInterface)
                        || ($runExposing && $transcriber instanceof ExposingInterface)
                    ) {
                        $transcriber->transcribe(
                            $compiledDeployment,
                            $client,
                            $promise
                        );
                    }
                }
            } catch (Throwable $error) {
                $mainPromise->fail($error);
            }
        };
    }
}
