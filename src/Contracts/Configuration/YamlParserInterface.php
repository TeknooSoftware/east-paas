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

namespace Teknoo\East\Paas\Contracts\Configuration;

use SensitiveParameter;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * To define parser able to read a yaml stream passed as string and return to a promise a valid array.
 * If the yaml is invalid, the fail method of the promise must be called.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface YamlParserInterface
{
    public const PARSE_EXCEPTION_ON_INVALID_TYPE = 2;
    public const PARSE_OBJECT = 4;
    public const PARSE_OBJECT_FOR_MAP = 8;
    public const PARSE_DATETIME = 32;
    public const PARSE_CONSTANT = 256;
    public const PARSE_CUSTOM_TAGS = 512;

    /**
     * @param PromiseInterface<array<string, mixed>, mixed> $promise
     * @param int-mask-of<YamlParserInterface::PARSE_*> $flags A bit field of self::PARSE_* constants
     */
    public function parse(
        #[SensitiveParameter] string $value,
        PromiseInterface $promise,
        int $flags = 0,
    ): YamlParserInterface;
}
