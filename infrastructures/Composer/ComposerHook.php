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

namespace Teknoo\East\Paas\Infrastructures\Composer;

use RuntimeException;
use Symfony\Component\Process\Process;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Throwable;

use function array_flip;
use function array_merge;
use function is_string;
use function preg_match;
use function reset;

/**
 * Hook to perform some operations with composer to install dependencies for PHP Project.
 * Available composer's commands are :
 * - dump-autoload
 * - dumpautoload
 * - exec
 * - install
 * - require
 * - run
 * - run-script
 * - update
 * - upgrade
 * - self-update
 * - selfupdate
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ComposerHook implements HookInterface
{
    /**
     * @var callable
     */
    private $factory;

    private ?string $path = null;

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
     * @param array{action?: string|null, arguments?: iterable<string>} $options
     * @return string[]
     */
    private function validateOptions(array $options): array
    {
        $globalOptions = [
            'quiet',
            'version',
            'ansi',
            'dev',
            'no-dev',
            'no-ansi',
            'no-interaction',
            'no-plugins',
            'no-scripts',
            'working-dir',
            'no-cache'
        ];

        $dumpOptions = [
            'optimize',
            'classmap-authoritative',
            'apcu',
            'ignore-platform-req',
            'ignore-platform-reqs',
            'strict-psr',
        ];

        $installOptions = [
            'prefer-source',
            'prefer-dist',
            'prefer-install',
            'dry-run',
            'no-suggest',
            'no-autoloader',
            'no-progress',
            'no-install',
            'audit',
            'optimize-autoloader',
        ];

        $runOptions = [
            '[a-zA-Z0-9\-_]+',
        ];

        $requireOptions = [
            'update-with-dependencies',
            'update-with-all-dependencies',
            'with-dependencies',
            'with-all-dependencies',
        ];

        $grantedCommands = [
            'dump-autoload' => array_merge($globalOptions, $dumpOptions),
            'dumpautoload' => array_merge($globalOptions, $dumpOptions),
            'exec' => array_merge($globalOptions, $runOptions),
            'install' => array_merge($globalOptions, $dumpOptions, $installOptions),
            'require' => array_merge($globalOptions, $dumpOptions, $installOptions, $requireOptions),
            'run' => array_merge($globalOptions, $runOptions),
            'run-script' => array_merge($globalOptions, $runOptions),
            'update' => array_merge($globalOptions, $dumpOptions, $installOptions),
            'upgrade' => array_merge($globalOptions, $dumpOptions, $installOptions),
        ];

        $args = [];
        if (!isset($options['action'])) {
            $cmd = (string) reset($options);
        } else {
            $cmd = $options['action'];
            $args = $options['arguments'] ?? [];
        }

        foreach ([$cmd, ...$args] as &$value) {
            if (!is_scalar($value)) {
                throw new RuntimeException('composer action and arguments must be scalars values');
            }

            if (preg_match('#[\&\||<|>;]#S', (string) $value)) {
                throw new RuntimeException('Pipe and redirection are forbidden');
            }
        }

        if (!isset($grantedCommands[$cmd])) {
            throw new RuntimeException("$cmd is forbidden");
        }

        $final = [$cmd];
        foreach ($args as &$arg) {
            $pattern = '#^' . implode('|', $grantedCommands[$cmd]) . '$#S';
            if (!preg_match($pattern, (string) $arg)) {
                throw new RuntimeException("$arg is not a granted option for $cmd");
            }

            $final[] = ' --' . $arg;
        }

        return $final;
    }

    /**
     * @param array{action?: string|null, arguments?: iterable<string>} $options
     */
    public function setOptions(array $options, PromiseInterface $promise): HookInterface
    {
        try {
            $this->options = $this->validateOptions($options);
        } catch (Throwable $error) {
            $promise->fail($error);

            return $this;
        }

        $promise->success();

        return $this;
    }

    public function run(PromiseInterface $promise): HookInterface
    {
        $command = ($this->factory)([$this->binary, ...$this->options], $this->path);
        if (!$command instanceof Process) {
            $promise->fail(new RuntimeException('Bad process manager'));

            return $this;
        }

        $command->run();

        if ($command->isSuccessful()) {
            $promise->success($command->getOutput());
        } else {
            $promise->fail(new RuntimeException((string) $command->getErrorOutput()));
        }

        return $this;
    }
}
