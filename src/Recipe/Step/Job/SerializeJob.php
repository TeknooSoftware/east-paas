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
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Foundation\Promise\Promise;
use Throwable;

/**
 * Step to serialize as json object a Job instance thanks to a serializer.
 * User can also add variables into the json object by adding value into they key `envVars` in the workplan.
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
class SerializeJob
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @param array<string, mixed> $envVars
     */
    public function __invoke(Job $job, ManagerInterface $manager, ClientInterface $client, array $envVars = []): self
    {
        $this->serializer->serialize(
            $job,
            'json',
            new Promise(
                static function (string $jobSerialized) use ($manager) {
                    $manager->updateWorkPlan(['jobSerialized' => $jobSerialized]);
                },
                fn (Throwable $error) => throw new RuntimeException(
                    'teknoo.east.paas.error.recipe.job.serialization_error',
                    500,
                    $error
                )
            ),
            [
                'add' => [
                    'variables' => $envVars,
                ],
            ]
        );



        return $this;
    }
}
