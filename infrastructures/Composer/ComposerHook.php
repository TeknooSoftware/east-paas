<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Composer;

use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;

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

    public function setOptions(array $options): HookInterface
    {
        $this->options = $options;

        return $this;
    }

    public function run(): HookInterface
    {
        $command = ($this->factory)([$this->binary, ...$this->options], $this->path);
        if (!$command instanceof Process) {
            throw new \RuntimeException('bad process');
        }

        $command->run();

        return $this;
    }
}
