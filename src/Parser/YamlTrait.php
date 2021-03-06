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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Parser;

use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * Trait to decode a yaml stream thanks to a YamlParserInterface.
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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
