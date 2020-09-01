<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\History;

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\Recipe\ChefInterface;

class ReceiveHistory
{
    public function __invoke(ServerRequestInterface $request, ChefInterface $chef): self
    {
        $chef->updateWorkPlan(['serializedHistory' => (string) $request->getBody()]);

        return $this;
    }
}
