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

use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\Contracts\Serializing\SerializerInterface;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;
use Teknoo\East\Paas\Recipe\Traits\ResponseTrait;
use Teknoo\Recipe\ChefInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Throwable;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DisplayError
{
    use ResponseTrait;
    use PsrFactoryTrait;

    public function __construct(
        private SerializerInterface $serializer,
        MessageFactoryInterface $messageFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->setMessageFactory($messageFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        ClientInterface $client,
        ChefInterface $chef,
        Throwable $throwable
    ): self {
        $this->serializer->serialize(
            $throwable,
            'json',
            new Promise(
                function (string $error) use ($client, $chef, $throwable) {
                    $client->acceptResponse(
                        static::buildResponse(
                            $error,
                            200,
                            'application/json',
                            $this->messageFactory,
                            $this->streamFactory
                        )
                    );

                    $chef->finish($throwable);
                }
            )
        );

        return $this;
    }
}
