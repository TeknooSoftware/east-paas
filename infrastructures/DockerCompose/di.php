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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose;

use Psr\Container\ContainerInterface;
use SensitiveParameter;
use Teknoo\East\Paas\Cluster\Directory;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerFactoryInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\RunnerInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Driver as DriverAlias; //To prevent a bug into PHP-DI
use Teknoo\East\Paas\Infrastructures\DockerCompose\TranscriberCollection as TranscriberCollectionAlias;
use Teknoo\East\Paas\Object\ClusterCredentials;

use function DI\create;
use function DI\decorate;
use function DI\get;

return [
    RunnerFactoryInterface::class => static function (ContainerInterface $container): RunnerFactoryInterface {
        //Placeholder factory wired during the scaffold phase. The Ansible-backed implementation
        //(RunnerFactory + AnsibleRunner) is introduced in the dedicated Ansible execution phase.
        return new class implements RunnerFactoryInterface {
            public function __invoke(
                string $url,
                #[SensitiveParameter] ?ClusterCredentials $credentials,
            ): RunnerInterface {
                throw new \RuntimeException('Docker Compose runner is not configured yet');
            }
        };
    },

    TranscriberCollectionInterface::class => get(TranscriberCollectionAlias::class),

    TranscriberCollectionAlias::class => static function (
        ContainerInterface $container
    ): TranscriberCollectionAlias {
        //Transcribers are registered (with their 5/10/10/10/30/32/40/50 priorities) in the dedicated
        //deploy/expose transcriber phases. The scaffold ships an empty collection.
        return new TranscriberCollectionAlias();
    },

    DriverAlias::class => create()
        ->constructor(
            get(RunnerFactoryInterface::class),
            get(TranscriberCollectionInterface::class)
        ),

    Directory::class => decorate(static function (Directory $previous, ContainerInterface $container): Directory {
        $previous->register('docker-compose', $container->get(DriverAlias::class));

        return $previous;
    }),
];
