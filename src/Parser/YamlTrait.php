<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Parser;

use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Foundation\Promise\PromiseInterface;

trait YamlTrait
{
    private YamlParserInterface $parser;

    public function setParser(YamlParserInterface $parser): self
    {
        $this->parser = $parser;

        return $this;
    }

    private function parseYaml(string &$configuration, PromiseInterface $promise): void
    {
        $this->parser->parse(
            $configuration,
            $promise
        );
    }
}
