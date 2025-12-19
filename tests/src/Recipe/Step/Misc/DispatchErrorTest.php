<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Misc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Recipe\Step\Misc\DispatchError;
use Throwable;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(DispatchError::class)]
class DispatchErrorTest extends TestCase
{
    private (ErrorFactoryInterface&MockObject)|(ErrorFactoryInterface&Stub)|null $errorFactory = null;

    public function geterrorFactoryMock(bool $stub = false): (ErrorFactoryInterface&Stub)|(ErrorFactoryInterface&MockObject)
    {
        if (!$this->errorFactory instanceof ErrorFactoryInterface) {
            if ($stub) {
                $this->errorFactory = $this->createStub(ErrorFactoryInterface::class);
            } else {
                $this->errorFactory = $this->createMock(ErrorFactoryInterface::class);
            }
        }

        return $this->errorFactory;
    }

    public function buildStep(): DispatchError
    {
        return new DispatchError($this->geterrorFactoryMock(true));
    }

    public function testInvoke(): void
    {
        $this->assertInstanceOf(DispatchError::class, $this->buildStep()(
            $this->createStub(ManagerInterface::class),
            $this->createStub(ClientInterface::class),
            $this->createStub(Throwable::class),
        ));
    }

}
