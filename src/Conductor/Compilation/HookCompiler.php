<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompilerInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
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
        if (\is_array($hooksLibrary)) {
            $this->hooksLibrary = $hooksLibrary;
        } else {
            $this->hooksLibrary = \iterator_to_array($hooksLibrary);
        }
    }

    public function compile(
        array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job
    ): CompilerInterface {
        foreach ($definitions as $name => &$hooksList) {
            foreach ($hooksList as $hookName => &$configuration) {
                if (!isset($this->hooksLibrary[$hookName])) {
                    throw new \DomainException("Hook $hookName not available");
                }

                $hook = clone $this->hooksLibrary[$hookName];
                $hook->setOptions(
                    (array) $configuration,
                    new Promise(
                        null,
                        static function (\Throwable $error) {
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