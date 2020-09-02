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
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use Psr\Container\ContainerInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Maclof\Kubernetes\Client as KubClient;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;

use function DI\create;
use function DI\get;

return [
    ClientFactoryInterface::class => function (ContainerInterface $container): ClientFactoryInterface {
        return new class ($container->get('teknoo.east.paas.worker.tmp_dir')) implements ClientFactoryInterface {
            /**
             * @var string[]
             */
            private array $files = [];

            private string $tmpDir;

            public function __construct(string $tmpDir)
            {
                $this->tmpDir = $tmpDir;
            }

            public function __invoke(string $master, ?ClusterCredentials $credentials): KubClient
            {
                $options = [
                    'master' => $master,
                ];

                if (null !== $credentials) {
                    if (!empty($content = $credentials->getServerCertificate())) {
                        $options['ca_cert'] = $this->write($content);
                    }

                    if (!empty($content = $credentials->getPublicKey())) {
                        $options['client_cert'] = $this->write($content);
                    }

                    if (!empty($content = $credentials->getPrivateKey())) {
                        $options['client_key'] = $this->write($content);
                    }
                }

                return new KubClient($options);
            }

            private function write(string $value): string
            {
                $fileName = \tempnam($this->tmpDir, 'east-paas-kube-') . '.paas';

                if (empty($fileName)) {
                    throw new \RuntimeException('Bad file temp name');
                }

                \file_put_contents($fileName, $value);
                \chmod($fileName, 0500);

                $this->files[] = $fileName;

                return $fileName;
            }

            private function delete(): void
            {
                foreach ($this->files as $file) {
                    if (!\file_exists($file)) {
                        continue;
                    }

                    \unlink($file);
                }

                $this->files = [];
            }

            public function __destruct()
            {
                $this->delete();
            }
        };
    },

    ClusterClientInterface::class => get(Client::class),
    Client::class => create()
        ->constructor(get(ClientFactoryInterface::class)),
];
