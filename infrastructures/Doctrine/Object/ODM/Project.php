<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Doctrine\Object\ODM;

use Teknoo\East\Paas\Object\Project as BaseProject;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Doctrine\Document\StandardTrait;

class Project extends BaseProject
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
