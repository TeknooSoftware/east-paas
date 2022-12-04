<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\East\Paas\Compilation;

use RuntimeException;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;

use function class_exists;

/**
 * Factory to build new CompiledDeploymentInterface instance.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
            throw new RuntimeException("Error, $className is not a valid CompiledDeploymentInterface class");
        }
    }

    public function build(
        int $version,
        string $namespace,
        bool $hierarchicalNamespaces,
        ?string $prefix
    ): CompiledDeploymentInterface {
        return new $this->className($version, $namespace, $hierarchicalNamespaces, $prefix);
    }

    public function getSchema(): string
    {
        return $this->xsdSchema;
    }
}
