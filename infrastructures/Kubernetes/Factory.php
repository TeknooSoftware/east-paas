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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use Maclof\Kubernetes\Client as KubClient;
use Maclof\Kubernetes\RepositoryRegistry;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;

class Factory implements ClientFactoryInterface
{
    /**
     * @var string[]
     */
    private array $files = [];

    private string $tmpDir;

    private bool $verify;

    public function __construct(string $tmpDir, bool $verify)
    {
        $this->tmpDir = $tmpDir;
        $this->verify = $verify;
    }

    public function __invoke(
        string $master,
        ?ClusterCredentials $credentials,
        ?RepositoryRegistry $repositoryRegistry = null
    ): KubClient {
        $options = [
            'master' => $master,
            'verify' => $this->verify,
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

            if (!empty($content = $credentials->getUsername())) {
                $options['username'] = $content;
            }

            if (!empty($content = $credentials->getPassword())) {
                $options['password'] = $content;
            }
        }

        return new KubClient($options, null, $repositoryRegistry);
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
}