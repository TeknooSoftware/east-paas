<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use Teknoo\East\Paas\Infrastructures\Kubernetes\Exception\BadTempFileException;
use Teknoo\Kubernetes\Client as KubClient;
use Teknoo\Kubernetes\RepositoryRegistry;
use Psr\Http\Client\ClientInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Teknoo\East\Paas\Object\ClusterCredentials;

use function chmod;
use function file_exists;
use function file_put_contents;
use function tempnam;
use function unlink;

/**
 * Factory in the DI to create, on demand, a new `Kubernetes Client` instance,
 * needed to execute manifest on the remote Kubernetes manager.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Factory implements ClientFactoryInterface
{
    /**
     * @var string[]
     */
    private array $files = [];

    /**
     * @var callable
     */
    private $tmpNameFunction;

    public function __construct(
        private readonly string $tmpDir,
        private readonly ?ClientInterface $httpClient = null,
        private readonly bool $sslVerify = true,
        private readonly ?int $timeout = null,
        ?callable $tmpNameFunction = null,
    ) {
        if ($tmpNameFunction) {
            $this->tmpNameFunction = $tmpNameFunction;
        } else {
            $this->tmpNameFunction = tempnam(...);
        }
    }

    public function __invoke(
        string $master,
        ?ClusterCredentials $credentials,
        ?RepositoryRegistry $repositoryRegistry = null
    ): KubClient {
        $options = [
            'master' => $master,
            'verify' => $this->sslVerify,
        ];

        if (!empty($this->timeout)) {
            $options['timeout'] = $this->timeout;
        }

        if (null !== $credentials) {
            if (!empty($content = $credentials->getCaCertificate())) {
                $options['ca_cert'] = $this->write($content);
            }

            if (!empty($content = $credentials->getClientCertificate())) {
                $options['client_cert'] = $this->write($content);
            }

            if (!empty($content = $credentials->getClientKey())) {
                $options['client_key'] = $this->write($content);
            }

            if (!empty($content = $credentials->getToken())) {
                $options['token'] = $content;
            }

            if (!empty($content = $credentials->getUsername())) {
                $options['username'] = $content;
            }

            if (!empty($content = $credentials->getPassword())) {
                $options['password'] = $content;
            }
        }

        return new KubClient(
            $options,
            $repositoryRegistry,
            $this->httpClient,
        );
    }

    private function write(string $value): string
    {
        $fileName = ($this->tmpNameFunction)($this->tmpDir, 'east-paas-kube-');

        if (false === $fileName) {
            throw new BadTempFileException('Bad temp file name in K3s factory');
        }

        file_put_contents($fileName, $value);
        chmod($fileName, 0555);

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
