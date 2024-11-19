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

namespace Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirement;

use DomainException;

use function array_keys;

/**
 * To manage and represent a set of features requirements to compile a PaaS file
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Set
{
    /**
     * @param array<int|string, string> $requirements
     */
    public function __construct(
        private array $requirements
    ) {
    }

    public function validate(string $name): self
    {
        if (isset($this->requirements[$name])) {
            unset($this->requirements[$name]);
        }

        return $this;
    }

    public function checkIfAllRequirementsAreValidated(): self
    {
        if (!empty($this->requirements)) {
            throw new DomainException(
                message: 'These requirements `' . implode('`, `', array_keys($this->requirements))
                    . '` are not validated',
                code: 404,
            );
        }

        return $this;
    }
}
