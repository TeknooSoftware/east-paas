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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Misc;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Recipe\Step\Misc\DispatchError;
use Throwable;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Misc\DispatchError
 */
class DispatchErrorTest extends TestCase
{
    private ?ErrorFactoryInterface $errorFactory = null;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ErrorFactoryInterface
     */
    public function geterrorFactoryMock(): ErrorFactoryInterface
    {
        if (!$this->errorFactory instanceof ErrorFactoryInterface) {
            $this->errorFactory = $this->createMock(ErrorFactoryInterface::class);
        }

        return $this->errorFactory;
    }

    public function buildStep(): DispatchError
    {
        return new DispatchError($this->geterrorFactoryMock());
    }

    public function testInvoke()
    {
        self::assertInstanceOf(
            DispatchError::class,
            $this->buildStep()(
                $this->createMock(ManagerInterface::class),
                $this->createMock(ClientInterface::class),
                $this->createMock(Throwable::class),
            )
        );
    }

}
