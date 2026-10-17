<?php

declare(strict_types=1);

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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose;

use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

use function array_filter;
use function array_values;
use function chmod;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_executable;
use function is_string;
use function mkdir;
use function preg_replace;
use function rmdir;
use function scandir;
use function str_ends_with;
use function symlink;
use function sys_get_temp_dir;
use function trim;
use function uniqid;
use function unlink;

/**
 * Helpers of the tests of the scripts pushed by the deploy playbook template, run with fake executables in a hermetic
 * PATH.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait JobScriptTestTrait
{
    private const string TEMPLATE = __DIR__ . '/../../../infrastructures/DockerCompose/templates/deploy.yml.template';

    /**
     * Fake `docker`, driven by the `FAKE_*` variables, logging its calls in `FAKE_LOG`
     */
    private const string FAKE_DOCKER = <<<'SH'
        #!/bin/sh
        echo "docker $*" >>"$FAKE_LOG"
        case $1 in
            compose)
                shift 5
                case $1 in
                    run)
                        if [ -n "${FAKE_RUN_FAIL:-}" ]; then
                            echo "no such service" >&2
                            exit 1
                        fi
                        if [ -n "${FAKE_RUN_EMPTY:-}" ]; then
                            exit 0
                        fi
                        n=$(cat "$FAKE_DIR/counter" 2>/dev/null || echo 0)
                        n=$((n + 1))
                        echo "$n" >"$FAKE_DIR/counter"
                        echo "cid$n"
                        ;;
                    up)
                        echo "progress of up"
                        if [ -n "${FAKE_UP_FAIL:-}" ]; then
                            echo "up failed" >&2
                            exit 1
                        fi
                        ;;
                esac
                ;;
            wait)
                # A newer run (concurrency replace) during this one
                if [ -n "${FAKE_CANCEL_ON_WAIT:-}" ]; then
                    : >"$FAKE_CANCEL"
                fi
                for lock in ${FAKE_PROBE_LOCKS:-}; do
                    # Without a command to run: the hermetic PATH has no `true`
                    (exec 7>>"$FAKE_STATE/$lock" && flock -n 7)
                    case $? in
                        0) echo "wait: $lock free" >>"$FAKE_LOG" ;;
                        1) echo "wait: $lock busy" >>"$FAKE_LOG" ;;
                        *) echo "wait: $lock not probed" >>"$FAKE_LOG" ;;
                    esac
                done
                if [ -n "${FAKE_WAIT_FAIL:-}" ]; then
                    echo "no such container" >&2
                    exit 1
                fi
                if [ -n "${FAKE_WAIT_SILENT:-}" ]; then
                    exit 0
                fi
                shift
                for id in "$@"; do
                    code=${FAKE_EXIT_CODE:-0}
                    for entry in ${FAKE_EXIT_CODES:-}; do
                        if [ "${entry%%=*}" = "$id" ]; then
                            code=${entry#*=}
                        fi
                    done
                    echo "$code"
                done
                ;;
            logs)
                echo "output of $2"
                ;;
            ps)
                service=
                regular=
                container=
                status=
                for argument in "$@"; do
                    case $argument in
                        label=com.docker.compose.service=*)
                            service=${argument#label=com.docker.compose.service=}
                            ;;
                        label=com.docker.compose.oneoff=False)
                            regular=1
                            ;;
                        id=*)
                            container=${argument#id=}
                            ;;
                        status=*)
                            status=${argument#status=}
                            ;;
                    esac
                done
                if [ -n "$container" ]; then
                    state=running
                    for entry in ${FAKE_STATES:-}; do
                        if [ "${entry%%=*}" = "$container" ]; then
                            state=${entry#*=}
                        fi
                    done
                    if [ "$state" = "$status" ]; then
                        echo "$container"
                    fi
                    exit 0
                fi
                if [ -n "$regular" ]; then
                    for empty in ${FAKE_PS_EMPTY:-}; do
                        if [ "$empty" = "$service" ]; then
                            exit 0
                        fi
                    done
                    echo "id-$service"
                    exit 0
                fi
                if [ -e "$FAKE_CANCEL" ]; then
                    echo "cancel marker present" >>"$FAKE_LOG"
                fi
                for id in ${FAKE_PS_IDS:-}; do
                    case $id in
                        *=*)
                            if [ "${id%%=*}" = "$service" ]; then
                                echo "${id#*=}"
                            fi
                            ;;
                        *)
                            echo "$id"
                            ;;
                    esac
                done
                ;;
        esac
        exit 0

        SH;

    private const string FAKE_TIMEOUT = <<<'SH'
        #!/bin/sh
        echo "timeout $*" >>"$FAKE_LOG"
        if [ -n "${FAKE_TIMEOUT:-}" ]; then
            exit 124
        fi
        shift
        exec "$@"

        SH;

    private string $dir = '';

    /**
     * @return array<mixed>
     */
    private static function parseTemplate(): array
    {
        //The placeholders substituted by the driver are not YAML
        $playbook = Yaml::parse(
            (string) preg_replace('#\{% [a-zA-Z]+ %\}#', '[]', (string) file_get_contents(self::TEMPLATE)),
        );
        self::assertIsArray($playbook);
        self::assertIsArray($playbook[0]);

        return $playbook[0];
    }

    /**
     * @return array<int, array<mixed>>
     */
    private static function tasks(): array
    {
        $tasks = self::parseTemplate()['tasks'] ?? null;
        self::assertIsArray($tasks);

        return array_values(array_filter($tasks, is_array(...)));
    }

    private static function extractScript(string $path): string
    {
        foreach (self::tasks() as $task) {
            $copy = $task['ansible.builtin.copy'] ?? null;
            if (
                is_array($copy)
                && is_string($copy['dest'] ?? null)
                && str_ends_with($copy['dest'], '/' . $path)
            ) {
                self::assertIsString($copy['content']);

                return $copy['content'];
            }
        }

        self::fail('The script ' . $path . ' is not pushed by the deploy playbook');
    }

    private static function lookup(string $tool): string
    {
        $lookup = new Process(['/bin/sh', '-c', 'command -v ' . $tool]);
        $lookup->mustRun();

        return trim($lookup->getOutput());
    }

    /**
     * @param array<string, string> $fakes
     * @param array<int, string> $tools
     * @param array<int, string> $scripts
     */
    private function createWorkingDirectory(array $fakes, array $tools, array $scripts): void
    {
        $this->dir = sys_get_temp_dir() . '/east-paas-runner-' . uniqid('', true);
        mkdir($this->dir . '/bin', 0700, true);
        mkdir($this->dir . '/tools', 0700, true);

        $fakes += [
            'docker' => self::FAKE_DOCKER,
            'timeout' => self::FAKE_TIMEOUT,
        ];
        foreach ($fakes as $name => $content) {
            file_put_contents($this->dir . '/bin/' . $name, $content);
            chmod($this->dir . '/bin/' . $name, 0700);
        }

        foreach ($tools as $tool) {
            symlink(self::lookup($tool), $this->dir . '/tools/' . $tool);
        }

        foreach ($scripts as $script) {
            $path = $this->dir . '/project/' . $script;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0700, true);
            }

            file_put_contents($path, self::extractScript($script));
            chmod($path, 0750);
        }
    }

    private static function removeDirectory(string $dir): void
    {
        foreach ((array) scandir($dir) as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                self::removeDirectory($path);

                continue;
            }

            unlink($path);
        }

        rmdir($dir);
    }

    protected function tearDown(): void
    {
        if ('' !== $this->dir && is_dir($this->dir)) {
            self::removeDirectory($this->dir);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideShells(): iterable
    {
        yield 'sh' => ['/bin/sh'];

        if (is_executable('/bin/dash')) {
            yield 'dash' => ['/bin/dash'];
        }
    }

    private function hermeticPath(): string
    {
        return $this->dir . '/bin:' . $this->dir . '/tools';
    }

    /**
     * @return array<int, string>
     */
    private function calls(): array
    {
        if (!file_exists($this->dir . '/calls.log')) {
            return [];
        }

        return array_values(
            array_filter(explode("\n", (string) file_get_contents($this->dir . '/calls.log')), 'strlen'),
        );
    }

    private static function regularContainerLookup(string $service): string
    {
        return 'docker ps -aq --filter label=com.docker.compose.project=prj --filter label=com.docker.compose.service='
            . $service . ' --filter label=com.docker.compose.oneoff=False';
    }
}
