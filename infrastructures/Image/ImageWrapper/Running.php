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

namespace Teknoo\East\Paas\Infrastructures\Image\ImageWrapper;

use Closure;
use RuntimeException;
use Teknoo\East\Paas\Infrastructures\Image\ImageWrapper;
use Symfony\Component\Process\Process;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;

use function array_keys;
use function implode;
use function hash;
use function str_replace;
use function substr;
use function trim;

use const PHP_EOL;

/**
 * State for the class ImageWrapper for the daughter instance present into the workplan
 *
 * @mixin ImageWrapper
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getUrl(): Closure
    {
        return function (): string {
            return (string) $this->url;
        };
    }

    private function getAuth(): Closure
    {
        return function (): ?XRegistryAuth {
            return $this->auth;
        };
    }

    private function hash(): Closure
    {
        return function (string $name): string {
            return substr(hash('sha256', $this->projectId . $name), 0, 10);
        };
    }

    /**
     * @param array<string, \Stringable> $variables
     */
    private function generateShellScript(): Closure
    {
        return function (
            array $variables,
            string $path,
            string $imageName,
            string $imageShortName,
            string $template
        ): string {
            $buildsArgs = '';
            if (!empty($variables)) {
                $variablesList = [];
                foreach (array_keys($variables) as $key) {
                    $variablesList[] = $key . '=$' . $key;
                }

                $buildsArgs = implode(' --build-arg', $variablesList);
            }

            return str_replace(
                [
                    '{% imagePath %}',
                    '{% binary %}',
                    '{% buildsArgs %}',
                    '{% imageName %}',
                    '{% imageShortName %}',
                ],
                [
                    $path,
                    $this->binary,
                    $buildsArgs,
                    $imageName,
                    $imageShortName,
                ],
                (string) $this->templates[$template]
            );
        };
    }

    /**
     * @param array<string, string> $paths
     * @param string[] $writables
     */
    private function generateDockerFile(): Closure
    {
        return function (string $fromImage, array $paths, array $writables = [], ?string $command = null): string {
            $output = "FROM $fromImage" . PHP_EOL;

            foreach ($paths as $sourcePath => $localPath) {
                $output .= "COPY $sourcePath $localPath" . PHP_EOL;
            }

            if (!empty($writables)) {
                $output .= 'USER root:root' . PHP_EOL;
                $output .= "RUN chown -R 1000:1000 " . implode(' ', $writables) . ' ; \ ' . PHP_EOL
                            . "chmod u+w " . implode(' ', $writables) . PHP_EOL;
                $output .= 'USER 1000:1000' . PHP_EOL;
            }

            if (!empty($command)) {
                $output .= "CMD /bin/sh -c '$command'" . PHP_EOL;
            }

            return $output . PHP_EOL;
        };
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function startProcess(): Closure
    {
        /**
         * @param \Symfony\Component\Process\Process<string> $process
         * @param array<string, \Stringable> $variables
         */
        return function (Process $process, array $variables): void {
            $authEnvs = [
                'PAAS_REGISTRY_USER' => '',
                'PAAS_REGISTRY_PWD' => '',
                'PAAS_REGISTRY_HOST' => '',
            ];

            if (null !== ($auth = $this->getAuth())) {
                $authEnvs = [
                    'PAAS_REGISTRY_USER' => $auth->getUsername(),
                    'PAAS_REGISTRY_PWD' => $auth->getPassword(),
                    'PAAS_REGISTRY_HOST' => $this->getUrl(),
                ];
            }

            /** @var array<string, string|\Stringable> $envs */
            $envs = $authEnvs + $variables;

            $process->setEnv($envs);

            $process->setTimeout($this->timeout);
            $process->start();
        };
    }

    /**
     * @param \Symfony\Component\Process\Process[] $processes
     * @param \Teknoo\Recipe\Promise\PromiseInterface<string, mixed> $promise
     */
    private function waitProcess(): Closure
    {
        /**
         * @param \Symfony\Component\Process\Process[] $processes
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
                $promise->reset();
                $promise->success(
                    trim(
                        $process->getOutput()
                        . PHP_EOL
                        . $process->getErrorOutput()
                    )
                );
            }

            if (null !== $error) {
                $promise->fail(new RuntimeException($error));
            }
        };
    }
}
