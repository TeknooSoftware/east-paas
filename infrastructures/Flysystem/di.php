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

namespace Teknoo\East\Paas\Infrastructures\Flysystem;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

use function DI\get;
use function DI\create;
use function DI\string;

return [
    Local::class => create()
        ->constructor(string('{teknoo.east.paas.worker.tmp_dir}')),
    Filesystem::class => create()
        ->constructor(get(Local::class)),
    JobWorkspaceInterface::class => get(Workspace::class),
    Workspace::class => create()
        ->constructor(get(Filesystem::class), string('{teknoo.east.paas.worker.tmp_dir}')),
];
