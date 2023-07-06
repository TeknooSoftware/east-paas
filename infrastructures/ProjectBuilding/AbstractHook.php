<?php

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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\ProjectBuilding;

use RuntimeException;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Exception\InvalidArgumentException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Throwable;

use function array_merge;
use function preg_match;
use function reset;

/**
 * Abstract class to wrap dependency manager or other compilation tools, like make, into your
 * deployment with PaaS.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractHook implements HookInterface
{
    /**
     * @var callable
     */
    private $factory;

    private ?string $path = '';

    private string $localPath = '';

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public function __construct(
        private readonly string $binary,
        callable $factory,
    ) {
        $this->factory = $factory;
    }

    public function setPath(string $path): HookInterface
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param array{action?: string|null, path?:string, arguments?: iterable<string>} $options
     */
    public function setOptions(array $options, PromiseInterface $promise): HookInterface
    {
        try {
            $this->options = $this->validateOptions($options);

            if (!empty($options['path'])) {
                $this->localPath = $options['path'];
            }
        } catch (Throwable $error) {
            $promise->fail($error);

            return $this;
        }

        $promise->success();

        return $this;
    }

    /**
     * @param array{action?: string|null, arguments?: iterable<string>} $options
     * @return string[]
     * @throws InvalidArgumentException
     */
    abstract protected function validateOptions(array $options): array;

    /**
     * @param array<string, string[]> $grantedCommands
     * @param array{0?: string, action?: string|null, arguments?: array<string>} $options
     * @return string[]
     * @throws InvalidArgumentException
     */
    protected function escapeOptions(
        array $grantedCommands,
        array $options,
    ): array {
        $args = [];
        if (!isset($options['action'])) {
            $cmd = (string) reset($options);
        } else {
            $cmd = $options['action'];
            $args = $options['arguments'] ?? [];
        }

        foreach ([$cmd, ...$args] as &$value) {
            if (!is_scalar($value)) {
                throw new InvalidArgumentException('Action and arguments must be scalars values');
            }

            if (preg_match('#[\&\|<>;]#S', (string) $value)) {
                throw new InvalidArgumentException('Pipe and redirection are forbidden');
            }
        }

        if (!isset($grantedCommands[$cmd])) {
            throw new InvalidArgumentException("$cmd is forbidden");
        }

        $final = [$cmd];
        foreach ($args as &$arg) {
            $pattern = '#^' . implode('|', $grantedCommands[$cmd]) . '$#S';
            if (preg_match($pattern, (string) $arg)) {
                if (1 === strlen($arg)) {
                    $final[] = '-' . $arg;
                } else {
                    $final[] = '--' . $arg;
                }
            } else {
                    $final[] = $arg;
            }
        }

        return $final;
    }

    public function run(PromiseInterface $promise): HookInterface
    {
        $command = ($this->factory)([$this->binary, ...$this->options], $this->path . $this->localPath);
        if (!$command instanceof Process) {
            $promise->fail(new RuntimeException('Bad process manager'));

            return $this;
        }

        $command->run();

        if ($command->isSuccessful()) {
            $promise->success($command->getOutput() . (string) $command->getErrorOutput());
        } else {
            $promise->fail(new RuntimeException((string) $command->getErrorOutput()));
        }

        return $this;
    }
}
