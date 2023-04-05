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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Job\History;

use function is_callable;

/**
 * Service to create a new unique serial number for this run.
 * The generator can be overloaded (for tests by example)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SerialGenerator
{
    private int $lastSerialNumber = 0;

    /**
     * @var callable
     */
    private $generator;

    public function __construct(
        int $firstSerialNumber = 0,
        ?callable $generator = null
    ) {
        $this->lastSerialNumber = $firstSerialNumber;

        if (!is_callable($generator)) {
            $generator = fn ($number) => ++$number;
        }

        $this->generator = $generator;
    }

    public function setGenerator(callable $generator): SerialGenerator
    {
        $this->generator = $generator;
        return $this;
    }

    public function getNewSerialNumber(): int
    {
        return $this->lastSerialNumber = ($this->generator)($this->lastSerialNumber);
    }
}
