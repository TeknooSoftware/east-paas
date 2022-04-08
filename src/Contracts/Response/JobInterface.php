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

namespace Teknoo\East\Paas\Contracts\Response;

use Teknoo\East\Foundation\Client\ResponseInterface as EastResponse;
use Teknoo\East\Paas\Object\Job as BaseJob;
use Teknoo\East\Common\Contracts\Object\ObjectInterface;
use Teknoo\Immutable\ImmutableInterface;

/**
 * To define a response representing a job entry instance, compliant with East's response interface and
 * Clients instances
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface JobInterface extends
    ObjectInterface,
    ImmutableInterface,
    EastResponse
{
    public function getJob(): BaseJob;
}
