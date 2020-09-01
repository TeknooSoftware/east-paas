<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
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
        ->constructor(string('{app.job.root}')),
    Filesystem::class => create()
        ->constructor(get(Local::class)),
    JobWorkspaceInterface::class => get(Workspace::class),
    Workspace::class => create()
        ->constructor(get(Filesystem::class), string('{app.job.root}')),
];
