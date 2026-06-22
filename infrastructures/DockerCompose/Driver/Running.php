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
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
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

            /**
             * @var \Teknoo\Recipe\Promise\Promise<
             *     mixed,
             *      \Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\AccumulatorInterface,
             *      mixed
             * > $promise
             */
            $promise = new Promise(
                function (
                    float $version,
                    string $prefix,
                    string $projectName
                ) use (
                    $namespace
                ): AccumulatorInterface {
                    $finalProjectName = $this->sanitizeProjectName($namespace . '-' . $prefix . '-' . $projectName);

                    return new Accumulator($finalProjectName, 'private', $this->networkDriver);
                }
            );

            //The Compose project isolates project AND environment: sanitizeDns("{namespace}-{projectName}").
            $compiledDeployment->withJobSettings($promise);

            $accumulator = $promise->fetchResult();
            if (!$accumulator instanceof AccumulatorInterface) {
                throw new InvalidConfigurationException();
            }

            return $accumulator;
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
                    "Missing the `$stage` Ansible playbook template",
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

            //Resolve each pushed file's `src` to its absolute working-dir path so the playbook `copy` tasks
            //can read the local file; `dest`/`mode` are preserved. These derived lists are baked into the
            //rendered playbook's `vars:` block so it is self-contained (no reliance on Ansible --extra-vars):
            //the locally written secret/config files (paas_files), the volumes flagged resetOnDeployment
            //(paas_reset_volumes), the during-deployment job services (paas_jobs) and, on the expose stage,
            //the per-ingress TLS cert/key files (paas_certs).
            $resolveCopySources = static fn (FileToCopy $entry): array =>
                $entry->withResolvedSource($workingAbsoluteDir)->toArray();

            if ($runExposing) {
                $playbookVars = [
                    '{% paasCerts %}' => json_encode(
                        array_map($resolveCopySources, $accumulator->getCertificatesToCopy()),
                        JSON_THROW_ON_ERROR,
                    ),
                ];
            } else {
                $playbookVars = [
                    '{% paasFiles %}' => json_encode(
                        array_map($resolveCopySources, $accumulator->getFilesToCopy()),
                        JSON_THROW_ON_ERROR,
                    ),
                    '{% paasResetVolumes %}' => json_encode(
                        $accumulator->getResetVolumes(),
                        JSON_THROW_ON_ERROR,
                    ),
                    '{% paasJobs %}' => json_encode(
                        $accumulator->getJobsToRun(),
                        JSON_THROW_ON_ERROR,
                    ),
                ];
            }

            $playbookAbsolutePath = $workingAbsoluteDir . '/' . $stage . '.yml';
            $this->workspaceFilesystem->write(
                $workingDir . '/' . $stage . '.yml',
                $this->renderPlaybook(
                    $stage,
                    $accumulator,
                    $composeAbsolutePath,
                    $traefikConfigAbsolutePath,
                    $playbookVars,
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

            //The playbook is self-contained (paas_files/paas_reset_volumes/paas_jobs/paas_certs are rendered
            //into its vars: block above); only paas_project is still forwarded as an extra var.
            $extraVars = [
                'paas_project' => $accumulator->getProjectName(),
            ];

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
    /**
     * @param array<string, string> $playbookVars placeholder => already-encoded (JSON) value
     */
    private function renderPlaybook(): Closure
    {
        return function (
            string $stage,
            AccumulatorInterface $accumulator,
            string $composePath,
            string $traefikConfigPath = '',
            array $playbookVars = [],
        ): string {
            $template = $this->templatesFilesystem->read($this->templates[$stage]);

            $replacements = [
                '{% project %}' => $accumulator->getProjectName(),
                '{% deployRoot %}' => $this->deployRoot,
                '{% network %}' => $accumulator->getNetworkName(),
                '{% traefikContainer %}' => $this->traefikContainer,
                '{% traefikDynamicDir %}' => $this->traefikDynamicDir,
                '{% traefikCertsDir %}' => $this->traefikCertsDir,
                '{% traefikConfigFile %}' => $traefikConfigPath,
                '{% composeFile %}' => $composePath,
            ] + $playbookVars;

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
