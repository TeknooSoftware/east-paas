<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Project;

use Teknoo\East\Paas\Object\Environment;
use Teknoo\Recipe\ChefInterface;

class GetEnvironment
{
    public function __invoke(string $envName, ChefInterface $chef): self
    {
        $chef->updateWorkPlan(['environment' => new Environment($envName)]);

        return $this;
    }
}
