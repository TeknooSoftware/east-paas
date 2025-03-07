<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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
 * @license     https://teknoo.software/license/mit         MIT License
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

use function is_string;
use function strtoupper;

/**
 * Compilation module able to convert `services` sections in paas.yaml file as Service instance.
 * The Service instance will be pushed into the CompiledDeploymentInterface instance.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ServiceCompiler implements CompilerInterface, ExtenderInterface
{
    use MergeTrait;

    private const KEY_PORTS = 'ports';
    private const KEY_LISTEN = 'listen';
    private const KEY_TARGET = 'target';
    private const KEY_POD_NAME = 'pod';
    private const KEY_PROTOCOL = 'protocol';
    private const KEY_INTERNAL = 'internal';
    private const KEY_EXTENDS = 'extends';

    /**
     * @param array<string, array<string, mixed>> $library
     */
    public function __construct(
        public readonly array $library,
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
                    Transport::from(strtoupper($config[self::KEY_PROTOCOL] ?? Transport::Tcp->value)),
                    !isset($config[self::KEY_INTERNAL]) || !empty($config[self::KEY_INTERNAL])
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
                    "teknoo.east.paas.error.recipe.job.extends-not-available:services:$libName",
                    400
                );
            }

            $config = self::arrayMergeRecursiveDistinct($this->library[$libName], $config);
        }

        return $this;
    }
}
