<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Configuration;

use SensitiveParameter;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Teknoo\East\Paas\Contracts\Configuration\YamlParserInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * Parser, built on Symfony Yaml paerser, able to read a yaml stream passed as string and return to a promise a valid
 * array. If the yaml is invalid, the fail method of the promise must be called.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class YamlParser implements YamlParserInterface
{
    public function __construct(
        private readonly Parser $parser
    ) {
    }

    public function parse(
        #[SensitiveParameter] string $value,
        PromiseInterface $promise,
        int $flags = 0,
    ): YamlParserInterface {
        try {
            /** @var array<string, mixed> $result */
            $result = $this->parser->parse($value, $flags);
            $promise->success($result);
        } catch (Throwable $throwable) {
            $promise->fail($throwable);
        }

        return $this;
    }
}
