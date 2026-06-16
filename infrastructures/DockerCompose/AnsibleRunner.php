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

use Asm\Ansible\Ansible;
use Asm\Ansible\Command\AnsiblePlaybookInterface;
use RuntimeException;
use SensitiveParameter;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function dirname;
use function trim;

use const PHP_EOL;

/**
 * `RunnerInterface` implementation wrapping the `asm/php-ansible` OOP library to run an `ansible-playbook`
 * locally; Ansible itself connects to the remote Docker host over SSH (the SSH user/private key come from
 * the `RunnerFactory` through `ClusterCredentials`).
 *
 * Success/failure is resolved exactly like `Infrastructures\Image\ImageWrapper\Running::waitProcess()`:
 * a zero exit code resolves the promise with the playbook output, a non-zero exit code fails it with a
 * `RuntimeException` carrying the collected output.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class AnsibleRunner implements RunnerInterface
{
    /**
     * @var callable(string $playbookPath): AnsiblePlaybookInterface
     */
    private $playbookFactory;

    /**
     * @param (callable(string $playbookPath): AnsiblePlaybookInterface)|null $playbookFactory factory
     *        building the `asm/php-ansible` playbook command for a given playbook file, with the timeout
     *        already applied and `play()` set (overridable for tests; the `Asm\Ansible\Ansible` class is
     *        `final` and cannot be doubled directly)
     */
    public function __construct(
        private readonly string $playbookBinary = 'ansible-playbook',
        private readonly ?int $timeout = null,
        private readonly ?string $sshUser = null,
        #[SensitiveParameter]
        private readonly ?string $privateKeyFile = null,
        ?callable $playbookFactory = null,
    ) {
        if (null !== $playbookFactory) {
            $this->playbookFactory = $playbookFactory;
        } else {
            $binary = $this->playbookBinary;
            $timeout = $this->timeout;
            $this->playbookFactory = static function (string $playbookPath) use (
                $binary,
                $timeout,
            ): AnsiblePlaybookInterface {
                $ansible = new Ansible(dirname($playbookPath), $binary);

                if (null !== $timeout) {
                    $ansible->setTimeout($timeout);
                }

                return $ansible->playbook()->play($playbookPath);
            };
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
            $playbook = ($this->playbookFactory)($playbookPath)
                ->inventoryFile($inventoryPath)
                ->extraVars($extraVars);

            if (!empty($this->sshUser)) {
                $playbook->user($this->sshUser);
            }

            if (!empty($this->privateKeyFile)) {
                $playbook->privateKey($this->privateKeyFile);
            }

            $output = '';
            $exitCode = $playbook->execute(
                static function (string $type, string $buffer) use (&$output): void {
                    $output .= $buffer;
                }
            );
        } catch (Throwable $error) {
            $promise->fail($error);

            return $this;
        }

        $output = trim($output);

        if (0 !== (int) $exitCode) {
            $promise->fail(
                new RuntimeException(
                    '' !== $output ? $output : 'Ansible playbook execution failed',
                )
            );

            return $this;
        }

        $promise->success($output . PHP_EOL);

        return $this;
    }
}
