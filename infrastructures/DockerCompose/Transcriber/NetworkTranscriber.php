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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber;

use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\GenericTranscriberInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * "Generic transcriber" declaring the project's dedicated, internal network in the Compose Specification
 * file (`{private: {driver: bridge, internal: true}}`) and recording the resolved network name so the
 * deploy playbook connects Traefik to it.
 *
 * Compose prefixes the declared network with the project name on the host (`{project}_private`); that
 * fully-qualified name is the one wired to Traefik through `GenerationInterface::wireNetworkToTraefik()`.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class NetworkTranscriber implements GenericTranscriberInterface
{
    use CommonTrait;

    public function __construct(
        private readonly string $networkDriver = 'bridge',
    ) {
    }

    public function transcribe(
        CompiledDeploymentInterface $compiledDeployment,
        GenerationInterface $generation,
        PromiseInterface $promise,
        DefaultsBag $defaultsBag,
        string $namespace,
    ): TranscriberInterface {
        $networkDriver = $this->networkDriver;

        $compiledDeployment->withJobSettings(
            static function (
                float $version,
                string $prefix,
                string $projectName,
            ) use (
                $generation,
                $namespace,
                $networkDriver,
                $promise,
            ): void {
                try {
                    $networkName = $generation->getDedicatedNetworkName();

                    $generation->addNetwork(
                        $networkName,
                        [
                            'driver' => $networkDriver,
                            'internal' => true,
                        ],
                    );

                    $project = self::sanitizeDns($namespace . '-' . $projectName);

                    $generation->wireNetworkToTraefik($project . '_' . $networkName);

                    $promise->success([
                        'networks' => [
                            $networkName => [
                                'driver' => $networkDriver,
                                'internal' => true,
                            ],
                        ],
                    ]);
                } catch (Throwable $error) {
                    $promise->fail($error);
                }
            }
        );

        return $this;
    }
}
