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

namespace Teknoo\East\Paas\Compilation\Compiler;

use Teknoo\East\Paas\Compilation\CompiledDeployment\AutomaticResource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceCapacityExceededException;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

use function array_keys;
use function floor;
use function in_array;

/**
 * Resource manager to manage, compute and stop the compilation when the sum of resources requirements in a deployment
 * file exceed quotas.
 * This manager will also share remaining resources when some containers have no definitions resources in a deployment
 * under quota
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ResourceManager
{
    private bool $freezed = false;

    /**
     * @var array<int, AutomaticResource[]>
     */
    private array $automaticReservations = [];

    /**
     * @var array<string, AvailabilityInterface>
     */
    private array $availableQuotas = [];

    public function updateQuotaAvailability(string $resourceType, AvailabilityInterface $newAvailability): self
    {
        if (
            $this->freezed
            && !isset($this->availableQuotas[$resourceType])
        ) {
            throw new ResourceCapacityExceededException(
                message: "Quota type `{$resourceType}` is not available for this deployment",
                code: 400,
            );
        }

        //Add the new availability capacities resources
        if (!isset($this->availableQuotas[$resourceType])) {
            $this->availableQuotas[$resourceType] = $newAvailability;

            return $this;
        }

        $this->availableQuotas[$resourceType] = $this->availableQuotas[$resourceType]->update($newAvailability);

        return $this;
    }

    public function freeze(): void
    {
        $this->freezed = true;
    }

    public function reserve(
        string $resourceType,
        string $require,
        string $limit,
        int $numberOfReplicas,
        ResourceSet $resourceSet,
    ): self {
        if (!isset($this->availableQuotas[$resourceType])) {
            throw new ResourceCapacityExceededException(
                "Quota type `{$resourceType}` is not available for this deployment",
            );
        }

        $this->availableQuotas[$resourceType]->reserve($require, $limit, $numberOfReplicas, $resourceSet);

        return $this;
    }

    /**
     * @param string[] $resourceTypeToExclude
     */
    public function prepareAutomaticsReservations(
        ResourceSet $resourceSet,
        int $numberOfReplicas,
        array $resourceTypeToExclude,
    ): self {
        if (empty($this->availableQuotas)) {
            return $this;
        }

        foreach (array_keys($this->availableQuotas) as $quotaType) {
            if (in_array($quotaType, $resourceTypeToExclude)) {
                continue;
            }

            $newResource = new AutomaticResource($quotaType);
            $this->automaticReservations[$numberOfReplicas][] = $newResource;

            $resourceSet->add($newResource);
        }

        return $this;
    }

    public function computeAutomaticReservations(): self
    {
        if (empty($this->automaticReservations)) {
            return $this;
        }

        //Count containers
        $containerCounters = [];
        foreach ($this->automaticReservations as $count => $resources) {
            /** @var AutomaticResource $resource */
            foreach ($resources as $resource) {
                $type = $resource->getType();
                $containerCounters[$type] = ($containerCounters[$type] ?? 0) + $count;
            }
        }

        $resourcesDistributions = [];
        foreach ($containerCounters as $type => $count) {
            $resourcesDistributions[$type] = (int) floor(100 / $count);
        }

        foreach ($this->automaticReservations as $resources) {
            /** @var AutomaticResource $resource */
            foreach ($resources as $resource) {
                $type = $resource->getType();
                $this->availableQuotas[$type]->updateResource(
                    $resource,
                    $resourcesDistributions[$type],
                );
            }
        }

        return $this;
    }
}
