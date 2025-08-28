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

namespace Teknoo\East\Paas\Compilation\Compiler;

use DomainException;
use InvalidArgumentException;
use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\IngressPath;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\ExtenderInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

use function array_unique;
use function array_values;
use function is_string;

/**
 * Compilation module able to convert `ingresses` sections in paas.yaml file as Ingress instance.
 * The Ingress instance will be pushed into the CompiledDeploymentInterface instance.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class IngressCompiler implements CompilerInterface, ExtenderInterface
{
    use MergeTrait;

    private const string KEY_HOST = 'host';

    private const string KEY_TLS = 'tls';

    private const string KEY_PROVIDER = 'provider';

    private const string KEY_SECRET = 'secret';

    private const string KEY_SERVICE = 'service';

    private const string KEY_SERVICE_NAME = 'name';

    private const string KEY_PORT = 'port';

    private const string KEY_PATHS = 'paths';

    private const string KEY_PATH = 'path';

    private const string KEY_HTTPS_BACKEND = 'https-backend';

    private const string KEY_META = 'meta';

    private const string KEY_ALIASES = 'aliases';

    private const string KEY_EXTENDS = 'extends';

    /**
     * @param array<string, array<string, mixed>> $library
     */
    public function __construct(
        private readonly array $library,
    ) {
    }

    public function compile(
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): CompilerInterface {
        foreach ($definitions as $name => &$config) {
            $paths = [];
            foreach ($config[self::KEY_PATHS] ?? [] as $path) {
                $paths[] = new IngressPath(
                    $path[self::KEY_PATH],
                    $path[self::KEY_SERVICE][self::KEY_SERVICE_NAME] ?? null,
                    (int) ($path[self::KEY_SERVICE][self::KEY_PORT] ?? null)
                );
            }

            $port = $config[self::KEY_SERVICE][self::KEY_PORT] ?? null;
            if (null !== $port) {
                $port = (int) $port;
            }

            $meta = $config[self::KEY_META] ?? [];
            if (!is_array($meta)) {
                $meta = [$meta => true];
            }

            $compiledDeployment->addIngress(
                $name,
                new Ingress(
                    name: $name,
                    host: $config[self::KEY_HOST],
                    provider: $config[self::KEY_SERVICE][self::KEY_PROVIDER] ?? null,
                    defaultServiceName: $config[self::KEY_SERVICE][self::KEY_SERVICE_NAME] ?? null,
                    defaultServicePort: $port,
                    paths: $paths,
                    tlsSecret: $config[self::KEY_TLS][self::KEY_SECRET] ?? null,
                    httpsBackend: !empty($config[self::KEY_HTTPS_BACKEND]),
                    meta: $meta,
                    aliases: array_values(array_unique($config[self::KEY_ALIASES] ?? [])),
                )
            );
        }

        return $this;
    }

    public function extends(
        array &$definitions,
    ): ExtenderInterface {
        foreach ($definitions as &$config) {
            if (!isset($config[self::KEY_EXTENDS])) {
                continue;
            }

            $libName = $config[self::KEY_EXTENDS];
            if (!is_string($libName)) {
                throw new InvalidArgumentException("teknoo.east.paas.error.recipe.job.extends-need-string", 400);
            }

            if (!isset($this->library[$libName])) {
                throw new DomainException(
                    "teknoo.east.paas.error.recipe.job.extends-not-available:ingress:$libName",
                    400
                );
            }

            $config = self::arrayMergeRecursiveDistinct($this->library[$libName], $config);
        }

        return $this;
    }
}
