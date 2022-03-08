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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11\HistorySentHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11\HistorySentHandler
 * @covers \Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11\RequestTrait
 */
class HistorySentHandlerTest extends TestCase
{
    use RequestTestTrait;

    public function testInvoke()
    {
        $this->doTest(
            (new HistorySentHandler(
                'foo',
                'bar',
                $this->getUriFactoryInterfaceMock(),
                $this->getRequestFactoryInterfaceMock(),
                $this->getStreamFactoryInterfaceMock(),
                $this->getClientInterfaceMock()
            )),
            HistorySentHandler::class,
            new HistorySent('foo', 'bar', 'foo', 'bar')
        );
    }
}