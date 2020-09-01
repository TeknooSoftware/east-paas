<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Job;

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\Recipe\ChefInterface;

class ReceiveJob
{
    public function __invoke(ServerRequestInterface $request, ChefInterface $chef): self
    {
        $chef->updateWorkPlan(['serializedJob' => (string) $request->getBody()]);

        return $this;
    }
}
