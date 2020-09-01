<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\Misc;

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;

class GetVariables
{
    private string $jsonContentType;

    public function __construct(string $jsonContentType = 'application/json')
    {
        $this->jsonContentType = $jsonContentType;
    }

    public function __invoke(
        ManagerInterface $manager,
        ServerRequestInterface $request,
        ClientInterface $client
    ): self {
        $contentType = $request->getHeader('Content-Type');
        if ($this->jsonContentType !== \current($contentType)) {
            $manager->updateWorkPlan(['envVars' => []]);

            return $this;
        }

        $manager->updateWorkPlan(
            [
                'envVars' => \json_decode((string) $request->getBody(), true)
            ]
        );

        return $this;
    }
}
