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
use function set_time_limit;
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function getUrl(): Closure
    {
        return fn(): string => (string) $this->url;
    }

    private function getAuth(): Closure
    {
        return fn(): ?XRegistryAuth => $this->auth;
    }

    private function hash(): Closure
    {
        return fn(string $name) => substr(hash('sha256', $this->projectId . $name), 0, 10);
    }

    private function setTimeout(): Closure
    {
        return function (): void {
            if (empty($this->timeout)) {
                set_time_limit(0);
            } else {
                set_time_limit(($this->timeout + self::GRACEFUL_TIME));
            }
        };
    }

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
                $this->templates[$template]
            );
        };
    }

    private function generateDockerFile(): Closure
    {
        return function (string $fromImage, array $paths, array $writables = [], ?string $command = null): string {
            $output = "FROM $fromImage" . PHP_EOL;

            foreach ($paths as $sourcePath => $localPath) {
                $output .= "COPY $sourcePath $localPath" . PHP_EOL;
            }

            if (!empty($writables)) {
                $spl = ' ; \ ' . PHP_EOL . 'chmod o+w ' ;
                $output .= 'USER root:root'. PHP_EOL;
                $output .= "RUN chmod a+w " . implode($spl, $writables) . PHP_EOL;
                $output .= 'USER 1000:1000'. PHP_EOL;
            }

            if (!empty($command)) {
                $output .= "CMD /bin/sh -c '$command'" . PHP_EOL;
            }

            return $output . PHP_EOL;
        };
    }

    private function startProcess(): Closure
    {
        /**
         * @param Process<string> $process
         * @param array<string, mixed> $variables
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

            $envs = $authEnvs + $variables;

            $process->setEnv($envs);

            $process->setTimeout($this->timeout);
            $process->start();
        };
    }

    private function waitProcess(): Closure
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
