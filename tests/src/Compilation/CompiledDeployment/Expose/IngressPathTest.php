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

namespace Teknoo\Tests\East\Paas\Compilation\CompiledDeployment\Expose;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath
 */
class IngressPathTest extends TestCase
{
    private function buildObject($internal = false): IngressPath
    {
        return new IngressPath('/foo', 'bar', 80);
    }

    public function testGetPath()
    {
        self::assertEquals(
            '/foo',
            $this->buildObject()->getPath()
        );
    }

    public function testGetPodName()
    {
        self::assertEquals(
            'bar',
            $this->buildObject()->getServiceName()
        );
    }

    public function testGetPorts()
    {
        self::assertEquals(
            80,
            $this->buildObject()->getServicePort()
        );
    }
}