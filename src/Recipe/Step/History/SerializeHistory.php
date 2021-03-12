<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\East\Paas\Recipe\Step\History;

use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SerializeHistory
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private SerializerInterface $serializer;

    public function __construct(
        SerializerInterface $serializer,
        MessageFactoryInterface $messageFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->serializer = $serializer;
        $this->setMessageFactory($messageFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(History $history, ManagerInterface $manager, ClientInterface $client): self
    {
        $this->serializer->serialize(
            $history,
            'json',
            new Promise(
                static function (string $historySerialized) use ($manager) {
                    $manager->updateWorkPlan(['historySerialized' => $historySerialized]);
                },
                static::buildFailurePromise(
                    $client,
                    $manager,
                    'teknoo.east.paas.error.recipe.history.serialization_error',
                    400,
                    $this->messageFactory,
                    $this->streamFactory
                )
            )
        );

        return $this;
    }
}
