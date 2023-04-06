<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getWorkspace(): Closure
    {
        return fn(): JobWorkspaceInterface => $this->workspace;
    }

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
                function ($repositoryPath, $workspacePath) use ($options, $promise): void {
                    $this->gitProcess->setWorkingDirectory($workspacePath);

                    $this->gitProcess->setEnv([
                        'GIT_SSH_COMMAND' => "ssh -i {$workspacePath}{$this->privateKeyFilename} "
                            . " -o IdentitiesOnly=yes -o StrictHostKeyChecking=no",
                        'JOB_CLONE_DESTINATION' => $repositoryPath . $options['path'],
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
                }
            );

            return $this;
        };
    }

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
