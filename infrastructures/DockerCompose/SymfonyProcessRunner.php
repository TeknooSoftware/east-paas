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

use RuntimeException;
use SensitiveParameter;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function json_encode;
use function trim;

use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

/**
 * `RunnerInterface` implementation calling the raw `ansible-playbook` binary through a Symfony
 * `Process`. A fake `Process` can be substituted in tests/Behat. This is the default `RunnerInterface`
 * built by `RunnerFactory`; the DI container may inject another implementation behind the same contract.
 *
 * Success/failure is resolved exactly like `Infrastructures\Image\ImageWrapper\Running::waitProcess()`:
 * a successful process resolves the promise with its output, a failed process fails it with a
 * `RuntimeException` carrying the error output.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class SymfonyProcessRunner implements RunnerInterface
{
    /**
     * @var callable(array<int, string> $command, ?float $timeout): Process
     */
    private $processFactory;

    /**
     * @param (callable(array<int, string> $command, ?float $timeout): Process)|null $processFactory
     *        factory building the Symfony `Process` from the command line (overridable for tests)
     */
    public function __construct(
        private readonly string $playbookBinary = 'ansible-playbook',
        private readonly ?float $timeout = null,
        private readonly ?string $sshUser = null,
        #[SensitiveParameter]
        private readonly ?string $privateKeyFile = null,
        ?callable $processFactory = null,
    ) {
        if (null !== $processFactory) {
            $this->processFactory = $processFactory;
        } else {
            $this->processFactory = static fn (array $command, ?float $timeout): Process => new Process(
                command: $command,
                timeout: $timeout,
            );
        }
    }

    public function run(
        string $playbookPath,
        string $inventoryPath,
        array $extraVars,
        #[SensitiveParameter] ?ClusterCredentials $credentials,
        PromiseInterface $promise,
    ): RunnerInterface {
        try {
            $command = [
                $this->playbookBinary,
                $playbookPath,
                '--inventory',
                $inventoryPath,
                '--extra-vars',
                json_encode($extraVars, JSON_THROW_ON_ERROR),
            ];

            if (!empty($this->sshUser)) {
                $command[] = '--user';
                $command[] = $this->sshUser;
            }

            if (!empty($this->privateKeyFile)) {
                $command[] = '--private-key';
                $command[] = $this->privateKeyFile;
            }

            $process = ($this->processFactory)($command, $this->timeout);
            $process->run();
        } catch (Throwable $error) {
            $promise->fail($error);

            return $this;
        }

        if (!$process->isSuccessful()) {
            $promise->fail(
                new RuntimeException(
                    trim($process->getErrorOutput()) ?: 'Ansible playbook execution failed',
                )
            );

            return $this;
        }

        $promise->success(
            trim(
                $process->getOutput()
                . PHP_EOL
                . $process->getErrorOutput()
            ) . PHP_EOL
        );

        return $this;
    }
}
