<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Maclof\Kubernetes\Client as KubClient;
use Teknoo\East\Paas\Contracts\Cluster\ClientInterface as ClusterClientInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;

use function DI\create;
use function DI\get;

return [
    ClientFactoryInterface::class => function (): ClientFactoryInterface {
        return new class implements ClientFactoryInterface {
            /**
             * @var string[]
             */
            private array $files = [];

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
                $fileName = \tempnam(\sys_get_temp_dir(), 'east-paas-') . '.paas';

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
