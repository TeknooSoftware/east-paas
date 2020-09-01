<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;

trait HookTrait
{
    /**
     * @param iterable<string, HookInterface> $hooksLibrary
     */
    private function compileHooks(iterable $hooksLibrary, CompiledDeployment $compiledDeployment): callable
    {
        return static function ($hookConfiguration) use ($hooksLibrary, $compiledDeployment): void {
            if (empty($hookConfiguration)) {
                return;
            }

            if (!\is_array($hooksLibrary)) {
                $hooksLibrary = \iterator_to_array($hooksLibrary);
            }

            foreach ($hookConfiguration as $name => $hooksList) {
                foreach ($hooksList as $hookName => $configuration) {
                    if (!isset($hooksLibrary[$hookName])) {
                        throw new \DomainException("Hook $hookName not available");
                    }

                    $hook = clone $hooksLibrary[$hookName];
                    $hook->setOptions((array) $configuration);

                    $compiledDeployment->defineHook($name . ':' . $hookName, $hook);
                }
            }
        };
    }
}
