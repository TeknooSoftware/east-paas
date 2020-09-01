<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Contracts\Configuration;

use Teknoo\East\Foundation\Promise\PromiseInterface;

interface YamlParserInterface
{
    public function parse(string $value, PromiseInterface $promise, int $flags = 0): YamlParserInterface;
}
