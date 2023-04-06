<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Configuration;

use Symfony\Component\Yaml\Parser;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Parser, built on Symfony Yaml paerser, able to read a yaml stream passed as string and return to a promise a valid
 * array. If the yaml is invalid, the fail method of the promise must be called.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class YamlParser implements YamlParserInterface
{
    public function __construct(
        private readonly Parser $parser
    ) {
    }

    public function parse(string $value, PromiseInterface $promise, int $flags = 0): YamlParserInterface
    {
        try {
            $promise->success($this->parser->parse($value, $flags));
        } catch (Throwable $throwable) {
            $promise->fail($throwable);
        }

        return $this;
    }
}
