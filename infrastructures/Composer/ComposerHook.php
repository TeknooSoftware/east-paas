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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Composer;

use RuntimeException;
use Symfony\Component\Process\Process;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Throwable;

use function array_flip;
use function preg_match;
use function reset;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ComposerHook implements HookInterface
{
    private string $binary;

    /**
     * @var callable
     */
    private $factory;

    private ?string $path = null;

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public function __construct(string $binary, callable $factory)
    {
        $this->binary = $binary;
        $this->factory = $factory;
    }

    public function setPath(string $path): HookInterface
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function validateOptions(array $options): void
    {
        $grantedCommands = array_flip([
            'dump-autoload',
            'dumpautoload',
            'exec',
            'install',
            'require',
            'run',
            'run-script',
            'update',
            'upgrade',
            'self-update',
            'selfupdate',
        ]);

        foreach ($options as &$option) {
            if (preg_match('#[\&\||<|>;]#iS', $option)) {
                throw new RuntimeException('Pipe and redirection are forbidden');
            }
        }

        if (!isset($grantedCommands[$cmd = reset($options)])) {
            throw new RuntimeException("$cmd is forbidden");
        }
    }

    public function setOptions(array $options, PromiseInterface $promise): HookInterface
    {
        try {
            $this->validateOptions($options);

            $this->options = $options;
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
