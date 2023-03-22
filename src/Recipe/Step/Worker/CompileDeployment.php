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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use RuntimeException;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Throwable;

/**
 * Step to decode, validate parse and compile the paas yaml file to a CompiledDeployment instance.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class CompileDeployment
{
    public function __invoke(
        ManagerInterface $manager,
        ClientInterface $client,
        ConductorInterface $conductor,
        ?string $storageIdentifier = null,
        ?string $storageSize = null,
        ?string $ociRegistryConfig = null,
    ): self {
        /** @var Promise<CompiledDeploymentInterface, mixed, mixed> $promise */
        $promise = new Promise(
            static function (CompiledDeploymentInterface $deployment) use ($manager): void {
                $manager->updateWorkPlan([
                    CompiledDeploymentInterface::class => $deployment,
                ]);
            },
            static fn(Throwable $error): ChefInterface => $manager->error(
                new RuntimeException(
                    'teknoo.east.paas.error.recipe.configuration.compilation_error',
                    500,
                    $error
                )
            )
        );

        $conductor->compileDeployment(
            promise: $promise,
            storageIdentifier: $storageIdentifier,
            storageSize: $storageSize,
            ociRegistryConfig: $ociRegistryConfig,
        );

        return $this;
    }
}
