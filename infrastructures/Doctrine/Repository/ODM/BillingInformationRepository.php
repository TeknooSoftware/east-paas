<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM;

use Teknoo\East\Website\Doctrine\DBSource\ODM\RepositoryTrait;
use Teknoo\East\Paas\Contracts\DbSource\Repository\BillingInformationRepositoryInterface;

class BillingInformationRepository implements BillingInformationRepositoryInterface
{
    use RepositoryTrait;
}
