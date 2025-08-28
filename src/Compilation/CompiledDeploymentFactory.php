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

namespace Teknoo\East\Paas\Compilation;

use Teknoo\East\Paas\Compilation\Exception\UnsupportedVersion;
use Teknoo\East\Paas\Compilation\Exception\WrongCompiledDeploymentClassException;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;

use function class_exists;

/**
 * Factory to build new CompiledDeploymentInterface instance.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CompiledDeploymentFactory implements CompiledDeploymentFactoryInterface
{
    /**
     * @param 'Teknoo\East\Paas\Compilation\CompiledDeployment' $className
     * @param array<string, string> $xsdSchema
     */
    public function __construct(
        private readonly string $className,
        private readonly array $xsdSchema
    ) {
        if (!class_exists($this->className)) {
            throw new WrongCompiledDeploymentClassException(
                "Error, $className is not a valid CompiledDeploymentInterface class"
            );
        }
    }

    public function build(
        float $version,
        ?string $prefix,
        ?string $projectName,
    ): CompiledDeploymentInterface {
        return new $this->className($version, $prefix, $projectName);
    }

    public function getSchema(string $version): string
    {
        return $this->xsdSchema[$version] ?? throw new UnsupportedVersion("PaaS about $version is not supported", 400);
    }
}
