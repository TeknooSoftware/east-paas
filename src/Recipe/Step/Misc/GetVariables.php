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

namespace Teknoo\East\Paas\Recipe\Step\Misc;

use Psr\Http\Message\MessageInterface;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;

use function current;
use function json_decode;

/**
 * Step to inject environment variables, as array, under the key `envVars` with the body of the message if has been
 * encoded as json.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class GetVariables
{
    public function __construct(
        private string $jsonContentType = 'application/json',
    ) {
    }

    public function __invoke(
        ManagerInterface $manager,
        MessageInterface $message,
        ClientInterface $client
    ): self {
        $contentType = $message->getHeader('Content-Type');
        if ($this->jsonContentType !== current($contentType)) {
            $manager->updateWorkPlan(['envVars' => []]);

            return $this;
        }

        $manager->updateWorkPlan(
            [
                'envVars' => json_decode((string) $message->getBody(), true)
            ]
        );

        return $this;
    }
}
