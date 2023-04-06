<?php

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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Laminas;

use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\Job\SendJob;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\ErrorFactory;

use function DI\create;
use function DI\get;

return [
    SendHistory::class => create(),
    SendHistoryInterface::class => get(SendHistory::class),

    SendJob::class => create(),
    SendJobInterface::class => get(SendJob::class),

    ErrorFactory::class => create(),
    ErrorFactoryInterface::class => get(ErrorFactory::class),
];
