<?php

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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM;

use Teknoo\East\Paas\Object\Account as BaseAccount;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Doctrine\StandardTrait;

/**
 * Account specialization in doctrine as document.
 * Implement States's Doctrine Document feature via the trait `Teknoo\States\Doctrine\StandardTrait`
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Account extends BaseAccount
{
    use AutomatedTrait;
    use StandardTrait {
        AutomatedTrait::updateStates insteadof StandardTrait;
    }

    public static function statesListDeclaration(): array
    {
        return [];
    }
}
