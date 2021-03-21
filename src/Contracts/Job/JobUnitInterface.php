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

namespace Teknoo\East\Paas\Contracts\Job;

use Teknoo\East\Foundation\Normalizer\Object\NormalizableInterface;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
interface JobUnitInterface extends NormalizableInterface
{
    public function getId(): string;

    public function configureCloningAgent(
        CloningAgentInterface $agent,
        JobWorkspaceInterface $workspace,
        PromiseInterface $promise
    ): JobUnitInterface;

    public function configureImageBuilder(
        ImageBuilder $builder,
        PromiseInterface $promise
    ): JobUnitInterface;

    public function configureCluster(
        Directory $clientsDirectory,
        PromiseInterface $promise
    ): JobUnitInterface;

    /**
     * @param array<string, mixed> $values
     */
    public function updateVariablesIn(
        array $values,
        PromiseInterface $promise
    ): JobUnitInterface;
}
