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

namespace Teknoo\East\Paas\Job\History;

use function is_callable;

/**
 * Service to create a new unique serial number for this run.
 * The generator can be overloaded (for tests by example)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
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
            $generator = fn ($number) => $number + 1;
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
