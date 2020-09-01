<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Docker;

use Teknoo\East\Paas\Infrastructures\Docker\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Docker\Contracts\ScriptWriterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface;

use function DI\get;

return [
    ProcessFactoryInterface::class => new class implements ProcessFactoryInterface {
        /**
         * @param array<string, mixed> $command
         * @return Process<mixed>
         */
        public function __invoke(array $command, string $cwd): Process
        {
            return new Process(
                $command,
                $cwd
            );
        }
    },

    ScriptWriterInterface::class => new class implements ScriptWriterInterface {
        private ?string $scriptFileName = null;

        public function __invoke(string $content): string
        {
            $this->scriptFileName = \tempnam(\sys_get_temp_dir(), 'east-paas-docker-') . '.sh';
            \file_put_contents($this->scriptFileName, $content);
            \chmod($this->scriptFileName, 0755);

            return $this->scriptFileName;
        }

        public function delete(): void
        {
            if (null === $this->scriptFileName || !\file_exists($this->scriptFileName)) {
                return;
            }

            \unlink($this->scriptFileName);
            $this->scriptFileName = null;
        }

        public function __destruct()
        {
            $this->delete();
        }
    },

    BuilderInterface::class => get(BuilderWrapper::class),
    BuilderWrapper::class => static function (ContainerInterface $container): BuilderWrapper {
        $timeout = 5 * 60; //5 minutes;
        if ($container->has('app.docker.build.timeout')) {
            $timeout = (int) $container->has('app.docker.build.timeout');
        }

        return new BuilderWrapper(
            'docker',
            [
                'image' => (string) \file_get_contents(__DIR__ . '/templates/image.template'),
                'volume' => (string) \file_get_contents(__DIR__ . '/templates/volume.template'),
            ],
            $container->get(ProcessFactoryInterface::class),
            $timeout,
            $container->get(ScriptWriterInterface::class),
            '_mount'
        );
    }
];
