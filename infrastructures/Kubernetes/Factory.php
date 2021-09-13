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
use RuntimeException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Throwable;

use function chmod;
use function file_exists;
use function file_put_contents;
use function tempnam;
use function unlink;

/**
 * Factory in the DI to create, on demand, a new `Kubernetes Client` instance,
 * needed to execute manifest on the remote Kubernetes manager.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Factory implements ClientFactoryInterface
{
    /**
     * @var string[]
     */
    private array $files = [];

    public function __construct(
        private string $tmpDir,
        private bool $verify,
    ) {
    }

    public function __invoke(
        string $master,
        ?ClusterCredentials $credentials,
        ?RepositoryRegistry $repositoryRegistry = null
    ): KubClient {
        $options = [
            'master' => $master,
        ];

        if (null !== $this->verify) {
            $options['verify'] = $this->verify;
        }

        if (null !== $credentials) {
            if (!empty($content = $credentials->getServerCertificate())) {
                $options['ca_cert'] = $this->write($content);

                if (!empty($options['verify'])) {
                    unset($options['verify']);
                }
            }

            if (!empty($content = $credentials->getToken())) {
                $options['token'] = $this->write($content);
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
        try {
            $fileName = tempnam($this->tmpDir, 'east-paas-kube-') . '.paas';
        } catch (Throwable $error) {
            throw new RuntimeException('Bad file temp name in K3s factory', 0, $error);
        }

        file_put_contents($fileName, $value);
        chmod($fileName, 0500);

        $this->files[] = $fileName;

        return $fileName;
    }

    private function delete(): void
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->files = [];
    }

    public function __destruct()
    {
        $this->delete();
    }
}
