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

namespace Teknoo\East\Paas\Object\Account;

use Closure;
use DateTimeInterface;
use RuntimeException;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Environment;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Common\Object\User;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

use function is_iterable;

/**
 * State representing an account fully completed, able to create new project.
 *
 * @mixin Account
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Active implements StateInterface
{
    use StateTrait;

    public function canIPrepareNewJob(): Closure
    {
        return function (Project $project, Job $job, DateTimeInterface $date, Environment $environment): Account {
            $project->configure(
                $job,
                $date,
                $environment,
                $this->getFullNamespace(),
                $this->isUseHierarchicalNamespaces()
            );

            return $this;
        };
    }

    public function verifyAccessToUser(): Closure
    {
        return function (User $user, PromiseInterface $promise): Account {
            $usersList = $this->getUsers();

            if (is_iterable($usersList)) {
                foreach ($usersList as $u) {
                    if ($u->getId() === $user->getId()) {
                        $promise->success(true);

                        return $this;
                    }
                }
            }

            $promise->fail(
                new RuntimeException('teknoo.east.paas.error.account.inactive', 403)
            );

            return $this;
        };
    }
}
