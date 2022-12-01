<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
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

namespace Teknoo\East\Paas\Infrastructures\Image;

use Teknoo\East\Paas\Infrastructures\Image\Contracts\ProcessFactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeployment\BuilderInterface;

use function DI\get;
use function file_get_contents;

return [
    ProcessFactoryInterface::class => new class implements ProcessFactoryInterface {
        /**
         * @return Process<mixed>
         */
        public function __invoke(string $cwd): Process
        {
            return new Process(
                ['sh'],
                $cwd
            );
        }
    },

    BuilderInterface::class => get(ImageWrapper::class),
    ImageWrapper::class => static function (ContainerInterface $container): ImageWrapper {
        $timeout = (float) (3 * 60); //3 minutes;
        if ($container->has('teknoo.east.paas.img_builder.build.timeout')) {
            $timeout = (float) $container->get('teknoo.east.paas.img_builder.build.timeout');
        }

        $binary = 'docker';
        if ($container->has('teknoo.east.paas.img_builder.cmd')) {
            $binary = (string) $container->get('teknoo.east.paas.img_builder.cmd');
        }

        return new ImageWrapper(
            $binary,
            [
                'image' => (string) file_get_contents(__DIR__ . '/templates/generic/image.template'),
                'embedded-volume-image' => (string) file_get_contents(
                    __DIR__ . '/templates/generic/embedded-volume-image.template'
                ),
                'volume' => (string) file_get_contents(__DIR__ . '/templates/generic/volume.template'),
            ],
            $container->get(ProcessFactoryInterface::class),
            (string) $container->get('teknoo.east.paas.img_builder.build.platforms'),
            $timeout
        );
    }
];
