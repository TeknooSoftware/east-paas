<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper;

use Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper;
use Symfony\Component\Process\Process;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Container\Volume;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

/**
 * @mixin BuilderWrapper
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getUrl(): \Closure
    {
        return function (): string {
            return (string) $this->url;
        };
    }

    private function getAuth(): \Closure
    {
        return function (): ?XRegistryAuth {
            return $this->auth;
        };
    }

    private function generateShellScriptForImage(): \Closure
    {
        return function (Image $image): string {
            $buildsArgs = '';
            $variables = $image->getVariables();
            if (!empty($variables)) {
                $variablesList = [];
                foreach (\array_keys($variables) as $key) {
                    $variablesList[] = $key . '=$' . $key;
                }

                $buildsArgs = \implode(' --build-arg', $variablesList);
            }

            $scriptContent = \str_replace(
                [
                    '{% imagePath %}',
                    '{% binary %}',
                    '{% buildsArgs %}',
                    '{% imageName %}',
                ],
                [
                    $image->getPath(),
                    $this->binary,
                    $buildsArgs,
                    $image->getUrl() . ':' . $image->getTag(),
                ],
                $this->templates['image']
            );

            return ($this->scriptWriter)($scriptContent);
        };
    }

    private function generateShellScriptForVolume(): \Closure
    {
        return function (Volume $volume, string $volumePath): string {
            $scriptContent = \str_replace(
                [
                    '{% volumePath %}',
                    '{% volumeTarget %}',
                    '{% volumeAdds %}',
                    '{% volumeMount %}',
                    '{% binary %}',
                    '{% volumeName %}',
                ],
                [
                    $volumePath,
                    \rtrim($volume->getTarget(), '/'),
                    \implode(' ', $volume->getPaths()),
                    $volume->getMountPath(),
                    $this->binary,
                    $volume->getUrl(),
                ],
                $this->templates['volume']
            );

            return ($this->scriptWriter)($scriptContent);
        };
    }

    private function setTimeout(): \Closure
    {
        return function (): void {
            if (empty($this->timeout)) {
                \set_time_limit(0);
            } else {
                \set_time_limit($this->timeout + self::GRACEFULTIME);
            }
        };
    }

    private function startProcess(): \Closure
    {
        /**
         * @param Process<string> $process
         * @param array<string, mixed> $variables
         */
        return function (Process $process, array $variables): void {
            $authEnvs = [
                'PAAS_DOCKER_USER' => '',
                'PAAS_DOCKER_PWD' => '',
                'PAAS_DOCKER_HOST' => '',
            ];

            if (null !== ($auth = $this->getAuth())) {
                $authEnvs = [
                    'PAAS_DOCKER_USER' => $auth->getUsername(),
                    'PAAS_DOCKER_PWD' => $auth->getPassword(),
                    'PAAS_DOCKER_HOST' => $auth->getServerAddress(),
                ];
            }

            $envs = \array_merge(
                $variables,
                $authEnvs
            );

            $process->setEnv($envs);

            $process->setTimeout($this->timeout);
            $process->start();
        };
    }
    private function waitProcess(): \Closure
    {
        /**
         * @param Process[] $processes
         */
        return function (iterable $processes, PromiseInterface $promise): void {
            $error = null;
            foreach ($processes as $process) {
                if (null !== $error) {
                    if ($process->isRunning()) {
                        $process->stop();
                    }

                    continue;
                }

                $process->wait();

                //On first error, stop all concurrents builds and return the error
                if (!$process->isSuccessful()) {
                    $error = $process->getErrorOutput();

                    continue;
                }

                //Execute the promise with the process output to allow log it
                $promise->success($process->getOutput());
            }

            if (null !== $error) {
                $promise->fail(new \RuntimeException($error));
            }
        };
    }
}
