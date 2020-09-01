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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Step\History;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Serializing\DeserializerInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

class DeserializeHistory
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private DeserializerInterface $deserializer;

    public function __construct(
        DeserializerInterface $deserializer,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->deserializer = $deserializer;
        $this->setResponseFactory($responseFactory);
        $this->setStreamFactory($streamFactory);
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
                static::buildFailurePromise(
                    $client,
                    $manager,
                    'teknoo.paas.error.recipe.history.mal_formed',
                    400,
                    $this->responseFactory,
                    $this->streamFactory
                )
            )
        );

        return $this;
    }
}
