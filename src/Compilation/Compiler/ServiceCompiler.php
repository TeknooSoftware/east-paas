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
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Transport;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\ExtenderInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

use function array_key_first;
use function is_string;
use function strtoupper;

/**
 * Compilation module able to convert `services` sections in paas.yaml file as Service instance.
 * The Service instance will be pushed into the CompiledDeploymentInterface instance.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ServiceCompiler implements CompilerInterface, ExtenderInterface
{
    use MergeTrait;

    private const string KEY_PORTS = 'ports';

    private const string KEY_LISTEN = 'listen';

    private const string KEY_TARGET = 'target';

    private const string KEY_POD_NAME = 'pod';

    private const string KEY_PROTOCOL = 'protocol';

    private const string KEY_INTERNAL = 'internal';

    private const string KEY_EXTENDS = 'extends';

    private const string KEY_INGRESS = 'ingress';

    private const string KEY_SERVICE = 'service';

    private const string KEY_NAME = 'name';

    private const string KEY_PORT = 'port';

    /**
     * @param array<string, array<string, mixed>> $library
     */
    public function __construct(
        public readonly array $library,
        private readonly ?IngressCompiler $ingressCompiler = null,
    ) {
    }

    /**
     * Shortcut available since PaaS v1.2 : an `ingress` option in a service definition will generate an ingress,
     * named as the service, with the service as default service (on the port defined in the `port` option or the
     * first listened port of the service)
     *
     * @param array<string, mixed> $ingressConfig
     * @param array<int, int> $ports
     */
    private function compileIngressShortcut(
        string $serviceName,
        array $ingressConfig,
        array $ports,
        CompiledDeploymentInterface $compiledDeployment,
        JobWorkspaceInterface $workspace,
        JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): void {
        if (null === $this->ingressCompiler) {
            throw new DomainException(
                "teknoo.east.paas.error.recipe.job.ingress-shortcut-unavailable:$serviceName",
                400
            );
        }

        $port = (int) ($ingressConfig[self::KEY_PORT] ?? array_key_first($ports));
        unset($ingressConfig[self::KEY_PORT]);

        $ingressConfig[self::KEY_SERVICE] = [
            self::KEY_NAME => $serviceName,
            self::KEY_PORT => $port,
        ];

        $definitions = [$serviceName => $ingressConfig];
        $this->ingressCompiler->compile(
            definitions: $definitions,
            compiledDeployment: $compiledDeployment,
            workspace: $workspace,
            job: $job,
            resourceManager: $resourceManager,
            defaultsBag: $defaultsBag,
        );
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
            $ports = [];
            foreach ($config[self::KEY_PORTS] as $row) {
                $ports[(int) $row[self::KEY_LISTEN]] = (int) $row[self::KEY_TARGET];
            }

            $compiledDeployment->addService(
                $name,
                new Service(
                    $name,
                    $config[self::KEY_POD_NAME] ?? $name,
                    $ports,
                    Transport::from(strtoupper((string) ($config[self::KEY_PROTOCOL] ?? Transport::Tcp->value))),
                    !isset($config[self::KEY_INTERNAL]) || !empty($config[self::KEY_INTERNAL])
                )
            );

            if (!empty($config[self::KEY_INGRESS])) {
                $this->compileIngressShortcut(
                    serviceName: $name,
                    ingressConfig: $config[self::KEY_INGRESS],
                    ports: $ports,
                    compiledDeployment: $compiledDeployment,
                    workspace: $workspace,
                    job: $job,
                    resourceManager: $resourceManager,
                    defaultsBag: $defaultsBag,
                );
            }
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
                    "teknoo.east.paas.error.recipe.job.extends-not-available:services:$libName",
                    400
                );
            }

            $config = self::arrayMergeRecursiveDistinct($this->library[$libName], $config);
        }

        return $this;
    }
}
