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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Git\Hook;

use Closure;
use RuntimeException;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook;
use Teknoo\East\Paas\Workspace\File;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin Hook
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getWorkspace(): Closure
    {
        return function (): JobWorkspaceInterface {
            return $this->workspace;
        };
    }

    private function clone(): Closure
    {
        return function (PromiseInterface $promise): Hook {
            $options = $this->options;

            $this->getWorkspace()->runInRoot(function ($path) use ($options, $promise) {
                $this->gitProcess->setEnv([
                    'GIT_SSH_COMMAND' => "ssh -i {$this->privateKeyFilename} -o IdentitiesOnly=yes",
                    'JOB_CLONE_DESTINATION' => $path . $options['path'],
                    'JOB_REPOSITORY' => $options['url'],
                    'JOB_BRANCH' => ($options['branch'] ?? 'main'),
                ]);

                $this->gitProcess->run();

                if (!$this->gitProcess->isSuccessFul()) {
                    $promise->fail(
                        new RuntimeException(
                            "Error while initializing repository: {$this->gitProcess->getErrorOutput()}"
                        )
                    );
                } else {
                    $promise->success();
                }
            });

            return $this;
        };
    }

    private function prepareThenClone(): Closure
    {
        return function (PromiseInterface $promise): Hook {
            $workspace = $this->getWorkspace();

            $workspace->writeFile(
                new File($this->privateKeyFilename, Visibility::Private, $this->options['key']),
                function ($path) use ($promise) {
                    $this->gitProcess->setWorkingDirectory($path);

                    $this->clone($promise);
                }
            );

            return $this;
        };
    }
}
