<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Configuration;

use Symfony\Component\Yaml\Parser;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

class YamlParser implements YamlParserInterface
{
    private Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function parse(string $value, PromiseInterface $promise, int $flags = 0): YamlParserInterface
    {
        try {
            $promise->success($this->parser->parse($value, $flags));
        } catch (\Throwable $error) {
            $promise->fail($error);
        }

        return $this;
    }
}
