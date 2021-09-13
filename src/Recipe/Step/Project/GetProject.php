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

namespace Teknoo\East\Paas\Recipe\Step\Project;

use DomainException;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Object\Project;
use Throwable;

/**
 * Step to load a persisted project from the DB source thanks to the project loaded and inject it into the workplan.
 * On any error, the error factory will be called.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class GetProject
{
    public function __construct(
        private ProjectLoader $projectLoader,
    ) {
    }

    public function __invoke(string $projectId, ManagerInterface $manager, ClientInterface $client): self
    {
        $this->projectLoader->load(
            $projectId,
            new Promise(
                static function (Project $project) use ($manager) {
                    $manager->updateWorkPlan([Project::class => $project]);
                },
                fn (Throwable $error) => throw new DomainException(
                    'teknoo.east.paas.error.recipe.project.not_found',
                    404,
                    $error
                )
            )
        );

        return $this;
    }
}
