<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Command\Steps;

use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Foundation\Http\ClientInterface as EastClient;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Misc\DispatchResultInterface;
use Teknoo\East\Paas\Contracts\Serializing\NormalizerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Website\Service\DatesService;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DisplayResult implements DispatchResultInterface
{
    private DatesService $dateTimeService;

    private NormalizerInterface $normalizer;

    public function __construct(DatesService $dateTimeService, NormalizerInterface $normalizer)
    {
        $this->dateTimeService = $dateTimeService;
        $this->normalizer = $normalizer;
    }

    public function __invoke(
        ManagerInterface $manager,
        EastClient $client,
        JobUnitInterface $job,
        $result = null,
        ?\Throwable $exception = null,
        ?OutputInterface $output = null
    ): DispatchResultInterface {
        if (!$output) {
            return $this;
        }

        if (empty($result)) {
            $result = [];
        }

        $this->dateTimeService->passMeTheDate(
            function (\DateTimeInterface $now) use ($result, $manager, $output) {
                $this->normalizer->normalize(
                    $result,
                    new Promise(
                        function ($extra) use ($manager, $now, $output) {
                            $history = new History(
                                null,
                                DispatchResultInterface::class,
                                $now,
                                true,
                                $extra
                            );

                            $manager->updateWorkPlan([
                                History::class => $history,
                                'historySerialized' => \json_encode($history),
                            ]);

                            $output->writeln((string) \json_encode($history));
                        }
                    ),
                    'json'
                );
            }
        );

        return $this;
    }
}