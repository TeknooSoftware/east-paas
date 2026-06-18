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
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator as AccumulatorImplementation;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\GenericTranscriberInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Exception\InvalidConfigurationException;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Value\FileToCopy;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\States\State\StateInterface;
use Teknoo\States\State\StateTrait;
use Throwable;

use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function is_string;
use function json_encode;
use function parse_url;
use function preg_replace;
use function str_replace;
use function strtolower;
use function trim;

use const JSON_THROW_ON_ERROR;
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

    private function createAccumulator(): Closure
    {
        return function (CompiledDeploymentInterface $compiledDeployment): AccumulatorInterface {
            $namespace = (string) $this->namespace;
            $project = $this->sanitizeProjectName($namespace);

            //The Compose project isolates project AND environment: sanitizeDns("{namespace}-{projectName}").
            $compiledDeployment->withJobSettings(
                function (float $version, string $prefix, string $projectName) use (&$project, $namespace): void {
                    $project = $this->sanitizeProjectName($namespace . '-' . $projectName);
                }
            );

            return new AccumulatorImplementation($project, 'private', $this->networkDriver);
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

    /**
     * Create a fresh, per-run working directory (a path relative to the workspace filesystem root) using the
     * injected factory.
     */
    private function createWorkingDir(): Closure
    {
        return function (): string {
            $factory = $this->tmpDirFactory;
            if (null === $factory) {
                throw new InvalidConfigurationException('Missing the working directory factory');
            }

            return $factory();
        };
    }

    /**
     * Serialize the accumulator, write the playbook, inventory and pushed files to a fresh working
     * directory through the workspace filesystem, run the matching Ansible playbook and resolve the main
     * promise with a cleaned result.
     *
     * @param PromiseInterface<array<string, mixed>, mixed> $mainPromise
     */
    private function applyAccumulator(): Closure
    {
        /**
         * @param PromiseInterface<array<string, mixed>, mixed> $mainPromise
         */
        return function (
            AccumulatorInterface $accumulator,
            bool $runExposing,
            PromiseInterface $mainPromise,
        ): void {
            $stage = match ($runExposing) {
                true => 'expose',
                false => 'deploy',
            };

            if (empty($this->templates[$stage])) {
                throw new InvalidConfigurationException(
                    "Missing the \"$stage\" Ansible playbook template",
                );
            }

            $workingDir = $this->createWorkingDir();
            $workingAbsoluteDir = $this->workspaceRoot . '/' . $workingDir;

            $composeFile = $accumulator->getComposeFile();
            $traefikConfig = $accumulator->getTraefikConfig();

            $composeAbsolutePath = $workingAbsoluteDir . '/compose.yaml';
            $this->workspaceFilesystem->write(
                $workingDir . '/compose.yaml',
                Yaml::dump($composeFile, 8, 4),
            );

            //On the expose stage the Traefik dynamic configuration is serialized to "<project>.yml" so the
            //playbook can drop it into Traefik's watched directory.
            $traefikConfigAbsolutePath = '';
            if ($runExposing) {
                $traefikConfigAbsolutePath = $workingAbsoluteDir . '/' . $accumulator->getProjectName() . '.yml';
                $this->workspaceFilesystem->write(
                    $workingDir . '/' . $accumulator->getProjectName() . '.yml',
                    Yaml::dump($traefikConfig, 8, 4),
                );
            }

            $playbookAbsolutePath = $workingAbsoluteDir . '/' . $stage . '.yml';
            $this->workspaceFilesystem->write(
                $workingDir . '/' . $stage . '.yml',
                $this->renderPlaybook(
                    $stage,
                    $accumulator,
                    $composeAbsolutePath,
                    $traefikConfigAbsolutePath,
                ),
            );

            $inventoryAbsolutePath = $workingAbsoluteDir . '/inventory.ini';
            $this->workspaceFilesystem->write(
                $workingDir . '/inventory.ini',
                $this->renderInventory((string) $this->master),
            );

            foreach ($accumulator->getFiles() as $relativePath => $content) {
                $this->workspaceFilesystem->write($workingDir . '/' . $relativePath, $content);
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
                if (!is_string($output)) {
                    $output = json_encode($output, JSON_THROW_ON_ERROR);
                }

                $mainPromise->success([
                    'compose' => $composeFile,
                    'traefik' => $traefikConfig,
                    'output' => $output,
                ]);
            };

            /** @var \Teknoo\Recipe\Promise\Promise<array<string, mixed>|string, mixed, mixed> $runnerPromise */
            $runnerPromise = new Promise(
                onSuccess: $onSuccess,
                onFail: static fn (#[SensitiveParameter] Throwable $error): mixed => $mainPromise->fail($error),
            );

            //Thread the accumulator's derived data into the playbook variables it loops over: the locally
            //written secret/config files (paas_files), the volumes flagged resetOnDeployment
            //(paas_reset_volumes), the during-deployment job services (paas_jobs) and the per-ingress TLS
            //cert/key files (paas_certs). Each file `src` is resolved to its absolute working-dir path so
            //the Ansible `copy` tasks can read the local file; `dest`/`mode` are preserved.
            $resolveCopySources = static fn (FileToCopy $entry): array =>
                $entry->withResolvedSource($workingAbsoluteDir)->toArray();

            $extraVars = [
                'paas_project' => $accumulator->getProjectName(),
            ];

            if ($runExposing) {
                $extraVars['paas_certs'] = array_map(
                    $resolveCopySources,
                    $accumulator->getCertificatesToCopy(),
                );
            } else {
                $extraVars['paas_files'] = array_map(
                    $resolveCopySources,
                    $accumulator->getFilesToCopy(),
                );
                $extraVars['paas_reset_volumes'] = $accumulator->getResetVolumes();
                $extraVars['paas_jobs'] = $accumulator->getJobsToRun();
            }

            $runner->run(
                playbookPath: $playbookAbsolutePath,
                inventoryPath: $inventoryAbsolutePath,
                extraVars: $extraVars,
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
            AccumulatorInterface $accumulator,
            string $composePath,
            string $traefikConfigPath = '',
        ): string {
            $template = $this->templatesFilesystem->read($this->templates[$stage]);

            $networks = $accumulator->getNetworksToWire();

            $replacements = [
                '{% project %}' => $accumulator->getProjectName(),
                '{% deployRoot %}' => $this->deployRoot,
                '{% network %}' => $networks[0] ?? '',
                '{% traefikContainer %}' => $this->traefikContainer,
                '{% traefikDynamicDir %}' => $this->traefikDynamicDir,
                '{% traefikCertsDir %}' => $this->traefikCertsDir,
                '{% traefikConfigFile %}' => $traefikConfigPath,
                '{% composeFile %}' => $composePath,
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
            $accumulator = $this->createAccumulator($compiledDeployment);
            $defaultsBag = $this->defaultsBag ?? new DefaultsBag();

            try {
                $promise = new Promise(
                    onSuccess: static function (): void {
                        //Per-resource success is accumulated in the Accumulator; nothing to do here.
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
                            $accumulator,
                            $promise,
                            $defaultsBag,
                            (string) $this->namespace,
                        );
                    }
                }

                $this->applyAccumulator($accumulator, $runExposing, $mainPromise);
            } catch (Throwable $error) {
                $mainPromise->fail($error);
            }
        };
    }
}
