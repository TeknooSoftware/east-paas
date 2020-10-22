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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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

    private function hash(): \Closure
    {
        return fn(string $name) => \substr(\sha1($this->projectId . $name), 0, 10);
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
                    '{% imageShortName %}',
                ],
                [
                    $image->getPath(),
                    $this->binary,
                    $buildsArgs,
                    $image->getUrl() . ':' . $image->getTag(),
                    $image->getName() . $this->hash($image->getName()),
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
                    '{% volumeShortName %}',
                    '{% Dockerfile %}'
                ],
                [
                    $volumePath,
                    \rtrim($volume->getTarget(), '/'),
                    \implode(' ', $volume->getPaths()),
                    $volume->getMountPath(),
                    $this->binary,
                    $volume->getUrl(),
                    $volume->getName() . $this->hash($volume->getName()),
                    'Dockerfile' . $volume->getName(),
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
                \set_time_limit(($this->timeout + self::GRACEFULTIME));
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
                    'PAAS_DOCKER_HOST' => $this->getUrl(),
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
