<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Flysystem;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

use function DI\get;
use function DI\create;
use function DI\string;

return [
    LocalFilesystemAdapter::class => create()
        ->constructor(string('{teknoo.east.paas.worker.tmp_dir}')),
    Filesystem::class => create()
        ->constructor(get(LocalFilesystemAdapter::class)),
    JobWorkspaceInterface::class => get(Workspace::class),
    Workspace::class => create()
        ->constructor(
            get(Filesystem::class),
            string('{teknoo.east.paas.worker.tmp_dir}'),
            string('{teknoo.east.paas.project_configuration_filename}'),
        ),
];
