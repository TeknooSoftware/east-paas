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

namespace Teknoo\East\Paas\Loader;

use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Common\Loader\LoaderTrait;
use Teknoo\East\Paas\Contracts\DbSource\Repository\ProjectRepositoryInterface;

/**
 * Object loader in charge of object `Teknoo\East\Paas\Object\Project`.
 * Must provide an implementation of `ProjectRepositoryInterface` to be able work.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 *
 * @implements LoaderInterface<Project>
 */
class ProjectLoader implements LoaderInterface
{
    /**
     * @use LoaderTrait<Project>
     */
    use LoaderTrait;

    public function __construct(ProjectRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}
