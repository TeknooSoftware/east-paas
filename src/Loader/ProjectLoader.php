<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Loader;

use Teknoo\East\Website\Loader\LoaderInterface;
use Teknoo\East\Website\Loader\LoaderTrait;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;

class ProjectLoader implements LoaderInterface
{
    use LoaderTrait;

    public function __construct(ProjectRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}
