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

namespace Teknoo\East\Paas\Recipe;

use Teknoo\East\Paas\Contracts\Recipe\AdditionalStepsInterface;
use Teknoo\Recipe\Bowl\BowlInterface;

use function ksort;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
     * @return iterable<BowlInterface|callable>
     */
    public function getIterator(): iterable
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
