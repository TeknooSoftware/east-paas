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

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use RuntimeException;
use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as VolumeBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Throwable;

/**
 * Step, run in the deployment workspace filesystem, to build all persisted volumes, defined in the compiled deployment
 * instance and push to the image repository.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class BuildVolumes
{
    public function __construct(
        private readonly DispatchHistoryInterface $dispatchHistory,
    ) {
    }

    public function __invoke(
        VolumeBuilder $builder,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        string $projectId,
        string $envName,
        #[SensitiveParameter] JobUnitInterface $jobUnit,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        /** @var Promise<string, mixed, mixed> $promise */
        $promise = new Promise(
            onSuccess: function (string $buildSuccess) use ($projectId, $envName, $jobUnit): void {
                ($this->dispatchHistory)(
                    $projectId,
                    $envName,
                    $jobUnit->getId(),
                    self::class . ':Result',
                    ['build_output' => $buildSuccess]
                );
            },
            onFail: static fn(#[SensitiveParameter] Throwable $error): ChefInterface => $manager->error(
                new RuntimeException(
                    'teknoo.east.paas.error.recipe.volumes.building_error',
                    500,
                    $error
                )
            ),
        );

        $workspace->runInRepositoryPath(
            static function (string $root) use ($builder, $compiledDeployment, $promise): void {
                $builder->buildVolumes(
                    $compiledDeployment,
                    $root,
                    $promise
                );
            }
        );

        return $this;
    }
}
