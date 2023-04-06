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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe;

use Teknoo\East\Paas\Contracts\Recipe\AdditionalStepsInterface;
use Teknoo\Recipe\Bowl\BowlInterface;
use Traversable;

use function ksort;

/**
 * Abstract class used by the DI to implement `AdditionalStepsInterface` collections to customize East PaaS cookbooks.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractAdditionalStepsList implements AdditionalStepsInterface
{
    /**
     * @var array<int, array<int,BowlInterface|callable>>
     */
    private array $steps = [];

    public function add(int $priority, BowlInterface | callable $step): self
    {
        $this->steps[$priority][] = $step;

        return $this;
    }

    /**
     * @return Traversable<BowlInterface|callable>
     */
    public function getIterator(): Traversable
    {
        $stepsList = $this->steps;
        ksort($stepsList);
        foreach ($stepsList as $priority => &$stepSubLists) {
            foreach ($stepSubLists as &$step) {
                yield $priority => $step;
            }
        }
    }
}
