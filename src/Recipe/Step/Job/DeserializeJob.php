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

namespace Teknoo\East\Paas\Recipe\Step\Job;

use RuntimeException;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Throwable;

/**
 * Step to deserialize an json encoded job into a job unit thanks to a deserializer and inject into the workplan.
 * Extra variables will be also directy available in the workplan under the key `extra`.
 * On any error, the error factory will be called.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DeserializeJob
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private DeserializerInterface $deserializer,
        private array $variables,
    ) {
    }

    public function __invoke(string $serializedJob, ManagerInterface $manager, ClientInterface $client): self
    {
        $this->deserializer->deserialize(
            $serializedJob,
            JobUnitInterface::class,
            'json',
            new Promise(
                static function (JobUnitInterface $jobUnit) use ($manager) {
                    $manager->updateWorkPlan([JobUnitInterface::class => $jobUnit]);
                    $jobUnit->runWithExtra(fn ($extra) => $manager->updateWorkPlan(['extra' => $extra]));
                },
                fn (Throwable $error) => throw new RuntimeException(
                    'teknoo.east.paas.error.recipe.job.mal_formed',
                    400,
                    $error
                )
            ),
            [
                'variables' => $this->variables
            ]
        );

        return $this;
    }
}
