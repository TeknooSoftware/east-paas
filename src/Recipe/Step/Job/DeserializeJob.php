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

namespace Teknoo\East\Paas\Recipe\Step\Job;

use RuntimeException;
use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Step to deserialize an json encoded job into a job unit thanks to a deserializer and inject into the workplan.
 * Extra variables will be also directy available in the workplan under the key `extra`.
 * On any error, the error factory will be called.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DeserializeJob
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private readonly DeserializerInterface $deserializer,
        private readonly array $variables,
    ) {
    }

    public function __invoke(
        #[SensitiveParameter] string $serializedJob,
        ManagerInterface $manager,
        ClientInterface $client,
    ): self {
        $this->deserializer->deserialize(
            $serializedJob,
            JobUnitInterface::class,
            'json',
            new Promise(
                static function (#[SensitiveParameter] JobUnitInterface $jobUnit) use ($manager): void {
                    $manager->updateWorkPlan([JobUnitInterface::class => $jobUnit]);
                    $jobUnit->runWithExtra(
                        static fn($extra): ChefInterface => $manager->updateWorkPlan(['extra' => $extra]),
                    );
                },
                static fn(#[SensitiveParameter] Throwable $error): ChefInterface => $manager->error(
                    new RuntimeException(
                        'teknoo.east.paas.error.recipe.job.mal_formed',
                        400,
                        $error,
                    ),
                ),
            ),
            [
                'variables' => $this->variables,
            ],
        );

        return $this;
    }
}
