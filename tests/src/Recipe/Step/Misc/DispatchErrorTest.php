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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Misc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Recipe\Step\Misc\DispatchError;
use Throwable;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DispatchError::class)]
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
