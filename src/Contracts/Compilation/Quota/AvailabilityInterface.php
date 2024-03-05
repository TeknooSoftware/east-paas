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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Contracts\Compilation\Quota;

use Teknoo\East\Paas\Compilation\CompiledDeployment\AutomaticResource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceCapacityExceededException;
use Teknoo\East\Paas\Compilation\Compiler\Exception\QuotasNotCompliantException;

/**
 * Interface to define a quota object during a deployment. The quota has a capacity will be decrease at each
 * pod's requirement. The quota instance support relative requirement (a % between 0 and 100) or a fixed value
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface AvailabilityInterface
{
    public function __construct(string $type, string $capacity, string $require, bool $isSoft);

    /**
     * Return the current available remaining capacity for this quota
     */
    public function getCapacity(): string;

    /**
     * Return the current available remaining require capacity for this quota
     */
    public function getRequire(): string;

    /**
     * To update the quota from the deployment file to decrease it.
     * @throws ResourceCapacityExceededException
     * @throws QuotasNotCompliantException
     */
    public function update(AvailabilityInterface $availability): AvailabilityInterface;

    /**
     * To reserve resources for a container
     * @throws ResourceCapacityExceededException
     */
    public function reserve(
        string $require,
        string $limit,
        int $numberOfReplicas,
        ResourceSet $set,
    ): AvailabilityInterface;

    /**
     * To update automatics resources created by the ResourceManager when a container has no resources requirements
     * @throws ResourceCapacityExceededException
     */
    public function updateResource(
        AutomaticResource $resource,
        int $limit,
    ): AvailabilityInterface;
}
