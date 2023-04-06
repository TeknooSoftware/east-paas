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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Loader;

use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Common\Loader\LoaderTrait;
use Teknoo\East\Paas\Contracts\DbSource\Repository\AccountRepositoryInterface;

/**
 * Object loader in charge of object `Teknoo\East\Paas\Object\Account`.
 * Must provide an implementation of `AccountRepositoryInterface` to be able work.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements LoaderInterface<Account>
 */
class AccountLoader implements LoaderInterface
{
    /**
     * @use LoaderTrait<Account>
     */
    use LoaderTrait;

    public function __construct(AccountRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
}
