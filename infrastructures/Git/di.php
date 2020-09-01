<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Git;

use GitWrapper\GitWrapper;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

use function DI\get;
use function DI\create;

return [
    CloningAgentInterface::class => get(CloningAgent::class),
    CloningAgent::class => create()
        ->constructor(get(GitWrapper::class)),
];
