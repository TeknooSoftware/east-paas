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

namespace Teknoo\East\Paas\Object\Job;

use Closure;
use DateTimeInterface;
use Teknoo\East\Paas\Object\Job;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * State representing a new job, at begining of its execution, checking job's attributes
 *
 * @mixin Job
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Validating implements StateInterface
{
    use StateTrait;

    public function validate(): Closure
    {
        return function (DateTimeInterface $date): Job {
            $this->addToHistory('teknoo.east.paas.jobs.configured', $date, false);

            return $this;
        };
    }
}
