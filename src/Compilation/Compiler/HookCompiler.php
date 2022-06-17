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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Compilation\Compiler;

use DomainException;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Throwable;

use function is_array;
use function iterator_to_array;

/**
 * Compilation module able to convert `hooks` sections in paas.yaml file as Hook instance, from the Hook library
 * into the CompiledDeploymentInterface instance.
 * Each hook instance injected are a clone from the library, configuration from paas.yaml are passed to the cloned
 * instance.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class HookCompiler implements CompilerInterface
{
    /**
     * @var array<string, HookInterface> $hooksLibrary
     */
    private array $hooksLibrary;

    /**
     * @param iterable<string, HookInterface> $hooksLibrary
     */
    public function __construct(iterable $hooksLibrary)
    {
        $this->hooksLibrary = is_array($hooksLibrary) ? $hooksLibrary : iterator_to_array($hooksLibrary);
    }

    public function compile(
        array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job,
        ?string $storageIdentifier = null
    ): CompilerInterface {
        foreach ($definitions as $name => &$hooksList) {
            foreach ($hooksList as $hookName => &$configuration) {
                if (!isset($this->hooksLibrary[$hookName])) {
                    throw new DomainException("Hook $hookName not available");
                }

                $hook = clone $this->hooksLibrary[$hookName];
                $hook->setOptions(
                    (array) $configuration,
                    new Promise(
                        null,
                        static function (Throwable $error): never {
                            throw $error;
                        }
                    )
                );

                $compiledDeployment->addHook($name . ':' . $hookName, $hook);
            }
        }

        return $this;
    }
}
