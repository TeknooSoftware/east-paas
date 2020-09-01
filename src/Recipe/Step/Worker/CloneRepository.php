<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;

class CloneRepository
{
    public function __invoke(CloningAgentInterface $agent): self
    {
        $agent->run();

        return $this;
    }
}
