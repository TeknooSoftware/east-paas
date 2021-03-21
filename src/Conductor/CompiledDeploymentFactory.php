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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Conductor;

use RuntimeException;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentFactoryInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;

use function class_exists;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class CompiledDeploymentFactory implements CompiledDeploymentFactoryInterface
{
    private string $className;

    private string $xsdSchema;

    public function __construct(string $className, string $xsdSchema)
    {
        if (!class_exists($className)) {
            throw new RuntimeException("Error, $className is not a valid CompiledDeploymentInterface class");
        }

        $this->className = $className;
        $this->xsdSchema = $xsdSchema;
    }

    public function build(int $version, string $namespace): CompiledDeploymentInterface
    {
        return new $this->className($version, $namespace);
    }

    public function getSchema(): string
    {
        return $this->xsdSchema;
    }
}
