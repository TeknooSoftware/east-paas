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
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Throwable;

/**
 * Step to configure the builder with the job and push it into the workplan.
 * (The builder injected is a clone of the original builder, the original is "immutable").
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ConfigureImagesBuilder
{
    public function __construct(
        private ImageBuilder $builder,
    ) {
    }

    public function __invoke(
        JobUnitInterface $jobUnit,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        /** @var Promise<ImageBuilder, mixed, mixed> $promise */
        $promise = new Promise(
            static function (ImageBuilder $builder) use ($manager) {
                $manager->updateWorkPlan([ImageBuilder::class => $builder]);
            },
            fn (Throwable $error) => $manager->error(
                new RuntimeException(
                    'teknoo.east.paas.error.recipe.images.configuration_error',
                    500,
                    $error
                )
            )
        );

        $jobUnit->configureImageBuilder($this->builder, $promise);

        return $this;
    }
}
