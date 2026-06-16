<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Driver;

use Closure;
use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\GenericTranscriberInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;
use Throwable;

/**
 * State for the class Driver for the daughter instance present into the workplan
 *
 * @mixin Driver
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getRunner(): Closure
    {
        return function (): RunnerInterface {
            return ($this->runnerFactory)(
                (string) $this->master,
                $this->credentials,
            );
        };
    }

    private function createGeneration(): Closure
    {
        return function (): GenerationInterface {
            return new class implements GenerationInterface {
                /**
                 * @return array<string, mixed>
                 */
                public function getComposeFile(): array
                {
                    return [];
                }

                /**
                 * @return array<string, mixed>
                 */
                public function getTraefikConfig(): array
                {
                    return [];
                }

                /**
                 * @return array<string, string>
                 */
                public function getFiles(): array
                {
                    return [];
                }

                /**
                 * @return array<int, string>
                 */
                public function getNetworksToWire(): array
                {
                    return [];
                }
            };
        };
    }

    /**
     * @param PromiseInterface<array<string, mixed>, mixed> $mainPromise
     */
    private function runTranscriber(): Closure
    {
        return function (
            CompiledDeploymentInterface $compiledDeployment,
            PromiseInterface $mainPromise,
            bool $runDeployment,
            bool $runExposing
        ): void {
            $generation = $this->createGeneration();
            $defaultsBag = $this->defaultsBag ?? new DefaultsBag();

            try {
                $promise = new Promise(
                    onSuccess: $mainPromise->allowReuse()->success(...),
                    onFail: static function (#[SensitiveParameter] Throwable $error): never {
                        //To break the foreach loop
                        throw $error;
                    }
                );
                $promise->allowReuse();

                foreach ($this->transcribers as $transcriber) {
                    if (
                        ($runDeployment && $transcriber instanceof GenericTranscriberInterface)
                        || ($runDeployment && $transcriber instanceof DeploymentInterface)
                        || ($runExposing && $transcriber instanceof ExposingInterface)
                    ) {
                        /**
                         * @var PromiseInterface<array<string, mixed>, mixed> $promise
                         */
                        $transcriber->transcribe(
                            $compiledDeployment,
                            $generation,
                            $promise,
                            $defaultsBag,
                            (string) $this->namespace,
                        );
                    }
                }

                $this->getRunner()->run(
                    playbookPath: '',
                    inventoryPath: '',
                    extraVars: [
                        'compose' => $generation->getComposeFile(),
                        'traefik' => $generation->getTraefikConfig(),
                        'networks' => $generation->getNetworksToWire(),
                    ],
                    credentials: $this->credentials,
                    promise: $mainPromise,
                );
            } catch (Throwable $error) {
                $mainPromise->fail($error);
            }
        };
    }
}
