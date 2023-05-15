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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\East\Paas\Workspace\File;
use Teknoo\Tests\East\Common\Object\Traits\PopulateObjectTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Workspace\File
 */
class FileTest extends TestCase
{
    use PopulateObjectTrait;

    /**
     * @return File
     */
    public function buildObject(): File
    {
        return new File('fooName', Visibility::Public, 'barFoo');
    }

    public function testGetName()
    {
        self::assertEquals(
            'fooName',
            $this->generateObjectPopulated()->getName()
        );
    }

    public function testGetVisibility()
    {
        self::assertEquals(
            Visibility::Public,
            $this->generateObjectPopulated()->getVisibility()
        );
    }

    public function testGetContent()
    {
        self::assertEquals(
            'barFoo',
            $this->generateObjectPopulated()->getContent()
        );
    }
}
