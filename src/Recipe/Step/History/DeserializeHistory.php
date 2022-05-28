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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\History;

use RuntimeException;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Step to deserialize an json encoded history thanks to a deserializer and inject into the workplan.
 * On any error, the error factory will be called.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DeserializeHistory
{
    public function __construct(
        private DeserializerInterface $deserializer,
    ) {
    }

    public function __invoke(string $serializedHistory, ManagerInterface $manager, ClientInterface $client): self
    {
        $this->deserializer->deserialize(
            $serializedHistory,
            History::class,
            'json',
            new Promise(
                static function (History $history) use ($manager) {
                    $manager->updateWorkPlan([History::class => $history]);
                },
                fn (Throwable $error) => $manager->error(
                    new RuntimeException(
                        'teknoo.east.paas.error.recipe.history.mal_formed',
                        400,
                        $error
                    )
                )
            )
        );

        return $this;
    }
}
