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

namespace Teknoo\East\Paas\Recipe\Step\Misc;

use Psr\Http\Message\MessageInterface;
use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;

use function current;
use function json_decode;

/**
 * Step to inject environment variables, as array, under the key `envVars` with the body of the message if has been
 * encoded as json.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class GetVariables
{
    public function __construct(
        private readonly string $jsonContentType = 'application/json',
    ) {
    }

    public function __invoke(
        ManagerInterface $manager,
        #[SensitiveParameter] MessageInterface $message,
        ClientInterface $client,
    ): self {
        $contentType = $message->getHeader('Content-Type');
        if ($this->jsonContentType !== current($contentType)) {
            $manager->updateWorkPlan(['envVars' => []]);

            return $this;
        }

        $manager->updateWorkPlan(
            [
                'envVars' => json_decode((string) $message->getBody(), true, 512, JSON_THROW_ON_ERROR)
            ]
        );

        return $this;
    }
}
