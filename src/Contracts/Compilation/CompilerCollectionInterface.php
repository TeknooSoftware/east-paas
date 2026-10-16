<?php

declare(strict_types=1);

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

namespace Teknoo\East\Paas\Contracts\Compilation;

use IteratorAggregate;
use Traversable;

/**
 * To define a collection of `CompilerInstance` to pass to Conductor to compile and to normalize a PaaS Yaml.
 * Several compilers can be registered on a same pattern of a version: they are called in their order of registration,
 * each one with the section of the PaaS Yaml matching the pattern.
 *
 * @extends IteratorAggregate<string, array<string, CompilerInterface[]>>
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @template TKey
 * @template-covariant TValue
 * @template-extends IteratorAggregate<string, array<string, CompilerInterface[]>>
 */
interface CompilerCollectionInterface extends IteratorAggregate
{
    /**
     * To register a compiler on a pattern for each version. With `$reset`, the compiler replaces the compilers already
     * registered on this pattern, else it is added after them.
     *
     * @param string[] $versions
     */
    public function add(array $versions, string $pattern, CompilerInterface $compiler, bool $reset = true): void;
}
