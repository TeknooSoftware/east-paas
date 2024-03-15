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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Compilation;

use Teknoo\East\Paas\Compilation\Exception\WrongCompiledDeploymentClassException;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;

use function class_exists;

/**
 * Factory to build new CompiledDeploymentInterface instance.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CompiledDeploymentFactory implements CompiledDeploymentFactoryInterface
{
    /**
     * @param 'Teknoo\East\Paas\Compilation\CompiledDeployment' $className
     */
    public function __construct(
        private readonly string $className,
        private readonly string $xsdSchema
    ) {
        if (!class_exists($this->className)) {
            throw new WrongCompiledDeploymentClassException(
                "Error, $className is not a valid CompiledDeploymentInterface class"
            );
        }
    }

    public function build(
        int $version,
        ?string $prefix,
        ?string $projectName,
    ): CompiledDeploymentInterface {
        return new $this->className($version, $prefix, $projectName);
    }

    public function getSchema(): string
    {
        return $this->xsdSchema;
    }
}
