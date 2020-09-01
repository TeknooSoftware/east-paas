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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor\Compilation;

use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
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
