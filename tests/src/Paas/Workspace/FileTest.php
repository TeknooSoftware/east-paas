<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Object;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Workspace\File;
use Teknoo\Tests\East\Website\Object\Traits\PopulateObjectTrait;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
        return new File('fooName', 'fooBar', 'barFoo');
    }

    public function testGetName()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['name' => 'fooBar'])->getName()
        );
    }

    public function testGetVisibility()
    {
        self::assertEquals(
            'fooBar',
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
