<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Git\Hook;

use InvalidArgumentException;
use Closure;
use RuntimeException;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook;
use Teknoo\East\Paas\Workspace\File;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

use function str_starts_with;

/**
 * @mixin Hook
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
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

    /**
     * @param \Teknoo\Recipe\Promise\PromiseInterface<mixed, mixed> $promise
     */
    private function clone(): Closure
    {
        return function (PromiseInterface $promise): Hook {
            $options = $this->options;

            if (str_starts_with(($options['url'] ?? ''), 'http:')) {
                $promise->fail(
                    new InvalidArgumentException(
                        'Error, the git client support only ssh and https protocol'
                    )
                );

                return $this;
            }

            $this->getWorkspace()->runInRepositoryPath(
                function (string $repositoryPath, string $workspacePath) use ($options, $promise): void {
                    $gitProcess = ($this->gitProcessFactory)(
                        'git clone -q --recurse-submodules '
                            . '-b "${:JOB_BRANCH}" "${:JOB_REPOSITORY}" "${:JOB_CLONE_DESTINATION}"'
                    );

                    $gitProcess->setWorkingDirectory($workspacePath);

                    $gitProcess->setEnv([
                        'GIT_SSH_COMMAND' => "ssh -i {$workspacePath}{$this->privateKeyFilename} "
                            . " -o IdentitiesOnly=yes -o StrictHostKeyChecking=no",
                        'JOB_CLONE_DESTINATION' => $repositoryPath . $options['path'],
                        'JOB_REPOSITORY' => $options['url'],
                        'JOB_BRANCH' => ($options['branch'] ?? 'main'),
                    ]);

                    $gitProcess->run();

                    if (!$gitProcess->isSuccessFul()) {
                        $promise->fail(
                            new RuntimeException(
                                "Error while initializing repository: {$gitProcess->getErrorOutput()}"
                            )
                        );
                    } else {
                        $promise->success();
                    }
                }
            );

            return $this;
        };
    }

    /**
     * @param \Teknoo\Recipe\Promise\PromiseInterface<mixed, mixed> $promise
     */
    private function prepareThenClone(): Closure
    {
        return function (PromiseInterface $promise): Hook {
            $workspace = $this->getWorkspace();

            if (str_starts_with(($this->options['url'] ?? ''), 'http')) {
                $this->clone($promise);

                return $this;
            }

            $workspace->writeFile(
                new File($this->privateKeyFilename, Visibility::Private, $this->options['key']),
                function () use ($promise) {
                    $this->clone($promise);
                }
            );

            return $this;
        };
    }
}
