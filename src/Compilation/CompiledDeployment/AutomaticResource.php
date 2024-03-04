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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation\CompiledDeployment;

use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceWrongConfigurationException;

/**
 * Mutable value object, Resource created automatically by the ResourceManager when a container has not a resource
 * section defined (or fully defined) when quota are applied to an account. (All containers must have resources sections
 * so the ResourceManager will share automatically available ressourcces for theses lost containers definitions)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class AutomaticResource extends Resource
{
    public function __construct(string $type)
    {
        parent::__construct($type, null, null);
    }

    public function setLimit(string $require, string $limit): self
    {
        if (null !== $this->limit) {
            throw new ResourceWrongConfigurationException(
                "Limit value for `{$this->getType()}` is already defined",
            );
        }

        $this->limit = $limit;
        $this->require = $require;

        return $this;
    }
}
