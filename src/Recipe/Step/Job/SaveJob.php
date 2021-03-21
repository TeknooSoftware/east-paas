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

use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Writer\JobWriter;
use Teknoo\Recipe\ChefInterface;
use Throwable;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SaveJob
{
    private JobWriter $jobWriter;

    public function __construct(JobWriter $jobWriter)
    {
        $this->jobWriter = $jobWriter;
    }

    public function __invoke(Job $job, ChefInterface $chef, ClientInterface $client): self
    {
        $this->jobWriter->save(
            $job,
            new Promise(
                null,
                static function (Throwable $error) use ($client, $chef) {
                    $client->errorInRequest($error);

                    $chef->finish($error);
                }
            )
        );

        return $this;
    }
}
