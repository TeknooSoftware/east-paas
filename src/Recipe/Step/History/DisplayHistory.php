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

namespace Teknoo\East\Paas\Recipe\Step\History;

use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\Object\History;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;
use Teknoo\East\Paas\Recipe\Traits\ResponseTrait;
use Teknoo\Recipe\ChefInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class DisplayHistory
{
    use ResponseTrait;
    use PsrFactoryTrait;

    public function __construct(MessageFactoryInterface $messageFactory, StreamFactoryInterface $streamFactory)
    {
        $this->setMessageFactory($messageFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        History $history,
        ClientInterface $client,
        ChefInterface $chef,
        string $historySerialized
    ): self {
        $client->acceptResponse(
            static::buildResponse(
                $historySerialized,
                200,
                'application/json',
                $this->messageFactory,
                $this->streamFactory
            )
        );

        $chef->finish($history);

        return $this;
    }
}
