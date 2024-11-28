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

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use RuntimeException;
use SensitiveParameter;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\ChefInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Paas\Contracts\Compilation\ConductorInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Throwable;

/**
 * Step to read the paas file yaml from the cloned source repository and load it into the conductor
 * to compile it.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ReadDeploymentConfiguration
{
    public function __invoke(
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        ConductorInterface $conductor,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        /** @var Promise<string, mixed, mixed> $promise */
        $promise = new Promise(
            onFail: static function (#[SensitiveParameter] Throwable $error) use ($manager): ChefInterface {
                $message = 'teknoo.east.paas.error.recipe.configuration.read_error';
                if (!empty($error->getMessage())) {
                    $message = $error->getMessage();
                }

                $code = 500;
                if (!empty($error->getCode())) {
                    $code = $error->getCode();
                }

                return $manager->error(
                    new RuntimeException(
                        $message,
                        $code,
                        $error,
                    ),
                );
            },
        );

        $workspace->loadDeploymentIntoConductor($conductor, $promise);

        return $this;
    }
}
