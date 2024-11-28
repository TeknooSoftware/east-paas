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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe\Step\Project;

use DomainException;
use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Object\Project;
use Throwable;

/**
 * Step to load a persisted project from the DB source thanks to the project loaded and inject it into the workplan.
 * On any error, the error factory will be called.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class GetProject
{
    public function __construct(
        private readonly ProjectLoader $projectLoader,
    ) {
    }

    public function __invoke(string $projectId, ManagerInterface $manager, ClientInterface $client): self
    {
        /** @var Promise<Project, mixed, mixed> $fetchedPromise */
        $fetchedPromise = new Promise(
            onSuccess: static function (Project $project) use ($manager): void {
                $manager->updateWorkPlan([Project::class => $project]);
            },
            onFail: static fn(#[SensitiveParameter] Throwable $error): ChefInterface => $manager->error(
                new DomainException(
                    'teknoo.east.paas.error.recipe.project.not_found',
                    404,
                    $error
                )
            ),
        );

        $this->projectLoader->load(
            $projectId,
            $fetchedPromise
        );

        return $this;
    }
}
