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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Driver;

use Closure;
use SensitiveParameter;
use Symfony\Component\Yaml\Yaml;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\GenerationInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Generation as GenerationImplementation;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\GenericTranscriberInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;
use Throwable;

use function array_keys;
use function array_values;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_string;
use function mkdir;
use function parse_url;
use function preg_replace;
use function str_replace;
use function strtolower;
use function sys_get_temp_dir;
use function trim;
use function uniqid;

use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_PORT;

/**
 * State for the class Driver for the daughter instance present into the workplan
 *
 * @mixin Driver
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Running implements StateInterface
{
    use StateTrait;

    private function createGeneration(): Closure
    {
        return function (CompiledDeploymentInterface $compiledDeployment): GenerationInterface {
            $namespace = (string) $this->namespace;
            $project = $this->sanitizeProjectName($namespace);

            //The Compose project isolates project AND environment: sanitizeDns("{namespace}-{projectName}").
            $compiledDeployment->withJobSettings(
                function (float $version, string $prefix, string $projectName) use (&$project, $namespace): void {
                    $project = $this->sanitizeProjectName($namespace . '-' . $projectName);
                }
            );

            return new GenerationImplementation($project);
        };
    }

    /**
     * Lowercase and DNS-sanitise the Compose project name, mirroring the transcribers' sanitizeDns().
     */
    private function sanitizeProjectName(): Closure
    {
        return function (string $value): string {
            $value = strtolower($value);
            $value = (string) preg_replace('#[^a-z0-9-]+#', '-', $value);
            $value = (string) preg_replace('#-+#', '-', $value);

            return trim($value, '-');
        };
    }

    private function createWorkingDir(): Closure
    {
        return function (): string {
            if (null !== $this->tmpDirFactory) {
                return ($this->tmpDirFactory)();
            }

            $base = '' !== $this->tmpDir ? $this->tmpDir : sys_get_temp_dir();
            $dir = $base . '/east-paas-compose-' . uniqid('', true);

            if (!is_dir($dir)) {
                mkdir($dir, 0700, true);
            }

            return $dir;
        };
    }

    /**
     * Serialize the accumulator, write the playbook, inventory and pushed files to a fresh working
     * directory, run the matching Ansible playbook and resolve the main promise with a cleaned result.
     *
     * @param PromiseInterface<array<string, mixed>, mixed> $mainPromise
     */
    private function applyGeneration(): Closure
    {
        /**
         * @param PromiseInterface<array<string, mixed>, mixed> $mainPromise
         */
        return function (
            GenerationInterface $generation,
            bool $runExposing,
            PromiseInterface $mainPromise,
        ): void {
            $stage = $runExposing ? 'expose' : 'deploy';

            if (empty($this->templates[$stage])) {
                throw new InvalidConfigurationException(
                    "Missing the \"$stage\" Ansible playbook template",
                );
            }

            $workingDir = $this->createWorkingDir();

            $composeFile = $generation->getComposeFile();
            $traefikConfig = $generation->getTraefikConfig();

            $composePath = $workingDir . '/compose.yaml';
            file_put_contents(
                $composePath,
                Yaml::dump($composeFile, 8, 4),
            );

            $playbookPath = $workingDir . '/' . $stage . '.yml';
            file_put_contents(
                $playbookPath,
                $this->renderPlaybook($stage, $generation, $workingDir, $composePath),
            );

            $inventoryPath = $workingDir . '/inventory.ini';
            file_put_contents(
                $inventoryPath,
                $this->renderInventory((string) $this->master),
            );

            foreach ($generation->getFiles() as $relativePath => $content) {
                $filePath = $workingDir . '/' . $relativePath;
                $fileDir = dirname($filePath);
                if (!is_dir($fileDir)) {
                    mkdir($fileDir, 0700, true);
                }

                file_put_contents($filePath, $content);
            }

            $runner = ($this->runnerFactory)((string) $this->master, $this->credentials);

            //The Compose/Traefik arrays hold only resource definitions and file references; the sensitive
            //file contents (secrets, certs) live in the accumulator's files and are never serialized into
            //the History result.
            $onSuccess = static function (array|string $output) use (
                $composeFile,
                $traefikConfig,
                $mainPromise,
            ): void {
                $mainPromise->success([
                    'compose' => $composeFile,
                    'traefik' => $traefikConfig,
                    'output' => is_string($output) ? $output : '',
                ]);
            };

            /** @var PromiseInterface<mixed, mixed> $runnerPromise */
            $runnerPromise = new Promise(
                onSuccess: $onSuccess,
                onFail: static fn (#[SensitiveParameter] Throwable $error): mixed => $mainPromise->fail($error),
            );

            $runner->run(
                playbookPath: $playbookPath,
                inventoryPath: $inventoryPath,
                extraVars: [
                    'paas_project' => $generation->getProjectName(),
                ],
                credentials: $this->credentials,
                promise: $runnerPromise,
            );
        };
    }

    /**
     * Render the playbook template by substituting the {% %} placeholders.
     */
    private function renderPlaybook(): Closure
    {
        return function (
            string $stage,
            GenerationInterface $generation,
            string $workingDir,
            string $composePath,
        ): string {
            $template = (string) file_get_contents($this->templates[$stage]);

            $networks = $generation->getNetworksToWire();

            $replacements = [
                '{% project %}' => $generation->getProjectName(),
                '{% deployRoot %}' => $this->deployRoot,
                '{% network %}' => $networks[0] ?? ($generation->getProjectName() . '_'
                    . $generation->getDedicatedNetworkName()),
                '{% traefikContainer %}' => $this->traefikContainer,
                '{% composeFile %}' => $composePath,
                '{% workingDir %}' => $workingDir,
            ];

            return str_replace(
                array_keys($replacements),
                array_values($replacements),
                $template,
            );
        };
    }

    /**
     * Build a single-host Ansible inventory from the cluster address (`ssh://user@host:port`, `host:port`
     * or `host`); the SSH user and private key are applied by the runner from the ClusterCredentials.
     */
    private function renderInventory(): Closure
    {
        return function (string $url): string {
            $host = parse_url($url, PHP_URL_HOST);
            if (empty($host)) {
                //No scheme: parse_url returns the whole string as path for "host:port" / "host"
                $path = (string) (parse_url($url, PHP_URL_PATH) ?: $url);
                $parts = explode(':', $path, 2);
                $host = $parts[0];
                $port = $parts[1] ?? null;
            } else {
                $port = parse_url($url, PHP_URL_PORT);
            }

            $line = (string) $host . ' ansible_host=' . (string) $host;
            $line .= ' ansible_port=' . (string) ($port ?? 22);

            return "[docker_host]\n" . $line . "\n";
        };
    }

    /**
     * @param PromiseInterface<array<string, mixed>, mixed> $mainPromise
     */
    private function runTranscriber(): Closure
    {
        /**
         * @param PromiseInterface<array<string, mixed>, mixed> $mainPromise
         */
        return function (
            CompiledDeploymentInterface $compiledDeployment,
            PromiseInterface $mainPromise,
            bool $runDeployment,
            bool $runExposing
        ): void {
            $generation = $this->createGeneration($compiledDeployment);
            $defaultsBag = $this->defaultsBag ?? new DefaultsBag();

            try {
                $promise = new Promise(
                    onSuccess: static function (): void {
                        //Per-resource success is accumulated in the Generation; nothing to do here.
                    },
                    onFail: static function (#[SensitiveParameter] Throwable $error): never {
                        //To break the foreach loop
                        throw $error;
                    }
                );
                $promise->allowReuse();

                foreach ($this->transcribers as $transcriber) {
                    if (
                        ($runDeployment && $transcriber instanceof GenericTranscriberInterface)
                        || ($runDeployment && $transcriber instanceof DeploymentInterface)
                        || ($runExposing && $transcriber instanceof ExposingInterface)
                    ) {
                        /**
                         * @var PromiseInterface<array<string, mixed>, mixed> $promise
                         */
                        $transcriber->transcribe(
                            $compiledDeployment,
                            $generation,
                            $promise,
                            $defaultsBag,
                            (string) $this->namespace,
                        );
                    }
                }

                $this->applyGeneration($generation, $runExposing, $mainPromise);
            } catch (Throwable $error) {
                $mainPromise->fail($error);
            }
        };
    }
}
