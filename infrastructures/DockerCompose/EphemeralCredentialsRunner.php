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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose;

use League\Flysystem\FilesystemOperator;
use SensitiveParameter;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\BadTempFileException;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

/**
 * `RunnerInterface` decorator built by `RunnerFactory` when credentials files (SSH private key, `known_hosts`)
 * have been materialized for a run, into a private directory dedicated to this runner. It owns this directory and
 * removes it as soon as the playbook has run, whatever its outcome, instead of letting it live on the worker as long
 * as the factory (a shared service). It is also removed on destruction when the runner is never run.
 *
 * A runner is single-use: once the files are removed, a new run would let SSH fallback on the worker user's own
 * keys, so a second run fails the promise without calling the decorated runner.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class EphemeralCredentialsRunner implements RunnerInterface
{
    private bool $hasRun = false;

    private bool $released = false;

    /**
     * @param FilesystemOperator $filesystem filesystem where the credentials files have been materialized
     * @param string $directory private directory, relative to the filesystem, holding the credentials files, to
     *        remove after the run
     */
    public function __construct(
        private readonly RunnerInterface $runner,
        private readonly FilesystemOperator $filesystem,
        private readonly string $directory,
    ) {
    }

    public function run(
        string $playbookPath,
        string $inventoryPath,
        array $extraVars,
        #[SensitiveParameter] ?ClusterCredentials $credentials,
        PromiseInterface $promise,
    ): RunnerInterface {
        if ($this->hasRun) {
            $promise->fail(
                new BadTempFileException(
                    'The credentials files of this runner have already been removed, a runner can run only once',
                ),
            );

            return $this;
        }

        $this->hasRun = true;

        try {
            $this->runner->run(
                playbookPath: $playbookPath,
                inventoryPath: $inventoryPath,
                extraVars: $extraVars,
                credentials: $credentials,
                promise: $promise,
            );
        } finally {
            $this->release();
        }

        return $this;
    }

    /**
     * Remove the directory of the credentials files. When the removal fails, the error is rethrown and the removal
     * is retried on destruction.
     */
    private function release(): void
    {
        if ($this->released) {
            return;
        }

        $this->filesystem->deleteDirectory($this->directory);
        $this->released = true;
    }

    public function __destruct()
    {
        try {
            $this->release();
        } catch (Throwable) {
            //A destructor must not throw, the directory can not be removed anymore
        }
    }
}
