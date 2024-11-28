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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment;

use Countable;
use IteratorAggregate;
use Traversable;

use function count;

/**
 * Collections of resources defined for a container, not for replicas of containers
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements IteratorAggregate<Resource>
 */
class ResourceSet implements IteratorAggregate, Countable
{
    /**
     * @param Resource[] $resources
     */
    public function __construct(
        private array $resources = [],
    ) {
    }

    /**
     * @return Traversable<Resource>
     */
    public function getIterator(): Traversable
    {
        yield from $this->resources;
    }

    public function count(): int
    {
        return count($this->resources);
    }

    public function add(Resource $resource): void
    {
        $this->resources[] = $resource;
    }
}
