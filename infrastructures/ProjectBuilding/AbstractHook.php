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

namespace Teknoo\East\Paas\Infrastructures\ProjectBuilding;

use RuntimeException;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\ProjectBuilding\Exception\InvalidArgumentException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Throwable;

use function is_string;
use function ltrim;
use function preg_match;
use function reset;
use function rtrim;
use function str_replace;
use function trim;

/**
 * Abstract class to wrap dependency manager or other compilation tools, like make, into your
 * deployment with PaaS.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractHook implements HookInterface
{
    /**
     * @var string[]
     */
    private readonly array $command;

    private readonly float $timeout;

    private readonly ProcessFactoryInterface $factory;

    private string $path = '';

    private string $localPath = '';

    /**
     * @var array<string>
     */
    private array $options = [];

    /**
     * @param string|string[] $command
     */
    public function __construct(
        string|array $command,
        float $timeout,
        ProcessFactoryInterface $factory,
    ) {
        if (is_string($command)) {
            $command = [$command];
        }

        $this->command = $command;
        $this->timeout = $timeout;
        $this->factory = $factory;
    }

    public function setPath(string $path): HookInterface
    {
        $this->path = rtrim($path, '/');

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
                $this->localPath = '/' . trim($options['path'], '/');
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
            $cmd = (string) $options['action'];
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

            /** @var string $cmd */
            throw new InvalidArgumentException("$cmd is forbidden");
        }

        $final = [$cmd];
        foreach ($args as &$arg) {
            $pattern = '#^(' . implode('|', $grantedCommands[$cmd]) . ')$#S';
            if (preg_match($pattern, (string) $arg)) {
                if (1 === strlen($arg)) {
                    $final[] = '-' . $arg;
                } else {
                    $final[] = '--' . $arg;
                }
            } else {
                $final[] = (string) $arg;
            }
        }

        /** @var string[] $final */
        return $final;
    }

    public function run(PromiseInterface $promise): HookInterface
    {
        $path = $this->path . $this->localPath;

        $command = str_replace(
            search: '${PWD}',
            replace: $path,
            subject: $this->command,
        );

        $command = ($this->factory)(
            command: [...$command, ...$this->options],
            cwd: $path,
            timeout: $this->timeout,
        );

        $command->run();

        if ($command->isSuccessful()) {
            $promise->success($command->getOutput() . $command->getErrorOutput());
        } else {
            $promise->fail(new RuntimeException($command->getErrorOutput()));
        }

        return $this;
    }
}
