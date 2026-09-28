<?php

declare(strict_types=1);

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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Visibility;
use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\Kubernetes\Client as KubClient;
use Teknoo\Kubernetes\RepositoryRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use RuntimeException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ScopedClientFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Exception\BadTempFileException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Exception\TokenFileNotAllowedException;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Factory;
use Teknoo\East\Paas\Object\ClusterCredentials;
use Teknoo\Kubernetes\Exception\MissingMasterOptionException;

use function array_shift;
use function bin2hex;
use function random_bytes;
use function sys_get_temp_dir;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Factory::class)]
class FactoryTest extends TestCase
{
    private const string TMP_DIR = '/worker/tmp';

    public function buildFactory(
        ?ClientInterface $client = null,
        ?FilesystemOperator $filesystem = null,
        ?callable $directoryNameFactory = null,
        bool $allowTokenFile = false,
        string $tmpDir = self::TMP_DIR,
    ): Factory {
        return new Factory(
            filesystem: $filesystem ?? new Filesystem(new InMemoryFilesystemAdapter()),
            tmpDir: $tmpDir,
            httpClient: $client,
            directoryNameFactory: $directoryNameFactory,
            allowTokenFile: $allowTokenFile,
        );
    }

    /**
     * Filesystem on the real temp dir, needed by the token's check which uses the same test than the Kubernetes
     * client
     */
    private function buildTempFilesystem(): FilesystemOperator
    {
        return new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir()));
    }

    private function buildFullCredentials(string $token = ''): ClusterCredentials
    {
        return new ClusterCredentials(
            caCertificate: 'caCert',
            clientCertificate: 'clientCert',
            clientKey: 'privateKey',
            token: $token,
        );
    }

    private function assertFilesArePrivate(FilesystemOperator $filesystem, string $directory): void
    {
        foreach (['ca.crt' => 'caCert', 'client.crt' => 'clientCert', 'client.key' => 'privateKey'] as $name => $value) {
            $this->assertSame($value, $filesystem->read($directory . '/' . $name));
            $this->assertSame(Visibility::PRIVATE, $filesystem->visibility($directory . '/' . $name));
        }
    }

    public function testInvokeWithoutCredentials(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(filesystem: $filesystem);

        $this->assertInstanceOf(KubClient::class, $factory('foo', null));

        //No credentials, no directory
        $this->assertSame([], $filesystem->listContents('', false)->toArray());
    }

    public function testInvokeOnDirectoryNameFactoryException(): void
    {
        $factory = $this->buildFactory(
            directoryNameFactory: static fn () => throw new RuntimeException('foo'),
        );

        $this->expectException(RuntimeException::class);
        $factory('foo', $this->buildFullCredentials());
    }

    public function testInvokeRefusesAnEmptyDirectoryName(): void
    {
        $factory = $this->buildFactory(
            directoryNameFactory: static fn (): string => '',
        );

        $this->expectException(BadTempFileException::class);
        $factory('foo', $this->buildFullCredentials());
    }

    public function testInvokeRefusesAnExistingDirectory(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $filesystem->write('kube-dir/foo', 'bar');

        $factory = $this->buildFactory(
            filesystem: $filesystem,
            directoryNameFactory: static fn (): string => 'kube-dir',
        );

        try {
            $factory('foo', $this->buildFullCredentials());

            $this->fail('An existing directory must not be reused');
        } catch (BadTempFileException) {
        }

        //Could have been created by someone else: never reused, nor removed
        unset($factory);
        $this->assertSame('bar', $filesystem->read('kube-dir/foo'));
        $this->assertFalse($filesystem->fileExists('kube-dir/client.key'));
    }

    public function testInvokeWithClientCertificate(): void
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            clientCertificate: 'foo',
            clientKey: 'privateBar',
        );

        $this->assertInstanceOf(KubClient::class, $factory('foo', $credential));
    }

    public function testInvokeWithToken(): void
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            caCertificate: 'caCert',
            token: 'privateBar',
        );

        $this->assertInstanceOf(KubClient::class, $factory('foo', $credential));
    }

    public function testInvokeWithUserCredentials(): void
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            username: 'foo',
            password: 'bar'
        );

        $this->assertInstanceOf(KubClient::class, $factory('foo', $credential));
    }

    public function testInvokeWithUserCredentialsWithRegistry(): void
    {
        $factory = $this->buildFactory();

        $credential = new ClusterCredentials(
            '',
            '',
            'foo',
            'bar'
        );

        $this->assertInstanceOf(
            KubClient::class,
            $factory('foo', $credential, $this->createStub(RepositoryRegistry::class)),
        );
    }

    public function testInvokeWritesPrivateFilesRemovedOnDestruct(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(
            filesystem: $filesystem,
            directoryNameFactory: static fn (): string => 'kube-dir',
        );

        $this->assertInstanceOf(KubClient::class, $factory('foo', $this->buildFullCredentials()));

        $this->assertFilesArePrivate($filesystem, 'kube-dir');

        unset($factory);

        $this->assertFalse($filesystem->directoryExists('kube-dir'));
    }

    public function testDefaultDirectoryNameIsRandom(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(filesystem: $filesystem);

        $factory('foo', $this->buildFullCredentials());
        $factory('foo', $this->buildFullCredentials());

        $directories = $filesystem->listContents('', false)->toArray();
        $this->assertCount(2, $directories);
        foreach ($directories as $directory) {
            $this->assertInstanceOf(DirectoryAttributes::class, $directory);
            $this->assertMatchesRegularExpression('#^east-paas-kube-[0-9a-f]{32}$#', $directory->path());
        }
    }

    public function testWithClientRemovesTheDirectoryOnceTheCallbackHasReturned(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(
            filesystem: $filesystem,
            directoryNameFactory: static fn (): string => 'kube-dir',
        );

        $called = false;
        $this->assertSame(
            $factory,
            $factory->withClient(
                'foo',
                $this->buildFullCredentials(),
                function (KubClient $client) use ($filesystem, &$called): void {
                    $called = true;

                    //Files must exist, and be private, while the client is lent
                    $this->assertFilesArePrivate($filesystem, 'kube-dir');
                },
            ),
        );

        $this->assertTrue($called);
        $this->assertFalse($filesystem->directoryExists('kube-dir'));
    }

    public function testWithClientRemovesTheDirectoryWhenTheCallbackThrows(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(
            filesystem: $filesystem,
            directoryNameFactory: static fn (): string => 'kube-dir',
        );

        try {
            $factory->withClient(
                'foo',
                $this->buildFullCredentials(),
                static fn (KubClient $client) => throw new RuntimeException('foo'),
            );

            $this->fail('The callback exception must be rethrown');
        } catch (RuntimeException $error) {
            $this->assertSame('foo', $error->getMessage());
        }

        $this->assertFalse($filesystem->directoryExists('kube-dir'));
    }

    public function testWithClientWithoutCredentials(): void
    {
        $factory = $this->buildFactory();
        $this->assertInstanceOf(ScopedClientFactoryInterface::class, $factory);

        $received = null;
        $factory->withClient(
            'foo',
            null,
            function (KubClient $client) use (&$received): void {
                $received = $client;
            },
            $this->createStub(RepositoryRegistry::class),
        );

        $this->assertInstanceOf(KubClient::class, $received);
    }

    public function testWithClientDoesNotRemoveTheDirectoriesOfOtherClients(): void
    {
        $names = ['kube-dir-1', 'kube-dir-2'];
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(
            filesystem: $filesystem,
            directoryNameFactory: static function () use (&$names): string {
                return (string) array_shift($names);
            },
        );

        $factory('foo', new ClusterCredentials(caCertificate: 'caCert'));

        $factory->withClient(
            'foo',
            new ClusterCredentials(clientKey: 'privateKey'),
            static function (KubClient $client): void {
            },
        );

        $this->assertTrue($filesystem->fileExists('kube-dir-1/ca.crt'));
        $this->assertFalse($filesystem->directoryExists('kube-dir-2'));

        unset($factory);

        $this->assertFalse($filesystem->directoryExists('kube-dir-1'));
    }

    public function testInvokeRemovesTheDirectoryWhenTheClientCanNotBeBuilt(): void
    {
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(
            filesystem: $filesystem,
            directoryNameFactory: static fn (): string => 'kube-dir',
        );

        try {
            $factory('', $this->buildFullCredentials());

            $this->fail('A client without master must not be built');
        } catch (MissingMasterOptionException) {
        }

        $this->assertFalse($filesystem->directoryExists('kube-dir'));
    }

    public function testInvokeKeepsTheOriginalErrorWhenTheRemovalFails(): void
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('directoryExists')->willReturn(false);
        $filesystem->method('deleteDirectory')->willThrowException(new RuntimeException('Unable to delete'));

        $factory = $this->buildFactory(filesystem: $filesystem);

        $this->expectException(MissingMasterOptionException::class);
        $factory('', $this->buildFullCredentials());
    }

    public function testWithClientRethrowsARemovalFailureAndRetriesOnDestruction(): void
    {
        $deleted = [];
        $attempts = 0;

        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('directoryExists')->willReturn(false);
        $filesystem->method('deleteDirectory')->willReturnCallback(
            function (string $path) use (&$deleted, &$attempts): void {
                //The first removal fails, the retry on destruction succeeds
                if (0 === $attempts++) {
                    throw new RuntimeException('Unable to delete');
                }

                $deleted[] = $path;
            }
        );

        $factory = $this->buildFactory(
            filesystem: $filesystem,
            directoryNameFactory: static fn (): string => 'kube-dir',
        );

        try {
            $factory->withClient(
                'foo',
                $this->buildFullCredentials(),
                static function (KubClient $client): void {
                },
            );

            $this->fail('The removal error must be rethrown');
        } catch (RuntimeException $error) {
            $this->assertSame('Unable to delete', $error->getMessage());
        }

        $this->assertSame([], $deleted);

        unset($factory);

        $this->assertSame(['kube-dir'], $deleted);
    }

    public function testDestructDoesNotThrowWhenTheRemovalFails(): void
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('directoryExists')->willReturn(false);
        $filesystem->method('deleteDirectory')->willThrowException(new RuntimeException('Unable to delete'));

        $factory = $this->buildFactory(filesystem: $filesystem);
        $factory('foo', $this->buildFullCredentials());

        unset($factory);

        $this->addToAssertionCount(1);
    }

    public function testWithClientOnTheLocalFilesystemUsesAPrivateDirectory(): void
    {
        $root = 'east-paas-test-' . bin2hex(random_bytes(8));
        $tempFilesystem = $this->buildTempFilesystem();
        $filesystem = new Filesystem(new LocalFilesystemAdapter(sys_get_temp_dir() . '/' . $root));

        try {
            $factory = $this->buildFactory(
                filesystem: $filesystem,
                directoryNameFactory: static fn (): string => 'kube-dir',
                tmpDir: sys_get_temp_dir() . '/' . $root,
            );

            $factory->withClient(
                'foo',
                $this->buildFullCredentials(),
                function (KubClient $client) use ($filesystem): void {
                    //0700 for the directory, 0600 for the files
                    $directories = $filesystem->listContents('', false)->toArray();
                    $this->assertCount(1, $directories);
                    $this->assertInstanceOf(DirectoryAttributes::class, $directories[0]);
                    $this->assertSame('kube-dir', $directories[0]->path());
                    $this->assertSame(Visibility::PRIVATE, $directories[0]->visibility());

                    $this->assertFilesArePrivate($filesystem, 'kube-dir');
                },
            );

            $this->assertFalse($filesystem->directoryExists('kube-dir'));
        } finally {
            $tempFilesystem->deleteDirectory($root);
        }
    }

    public function testInvokeRefusesATokenDesignatingAFile(): void
    {
        $tokenFile = 'east-paas-test-token-' . bin2hex(random_bytes(8));
        $tempFilesystem = $this->buildTempFilesystem();
        $tempFilesystem->write($tokenFile, 'token');

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(filesystem: $filesystem);

        try {
            $factory('foo', $this->buildFullCredentials(sys_get_temp_dir() . '/' . $tokenFile));

            $this->fail('A token designating a file must be refused');
        } catch (TokenFileNotAllowedException $error) {
            //The token value must never be leaked
            $this->assertStringNotContainsString($tokenFile, $error->getMessage());
        } finally {
            $tempFilesystem->delete($tokenFile);
        }

        //Refused before writing any credentials file
        $this->assertSame([], $filesystem->listContents('', false)->toArray());
    }

    public function testWithClientRefusesATokenDesignatingAFile(): void
    {
        $tokenFile = 'east-paas-test-token-' . bin2hex(random_bytes(8));
        $tempFilesystem = $this->buildTempFilesystem();
        $tempFilesystem->write($tokenFile, 'token');

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $factory = $this->buildFactory(filesystem: $filesystem);

        $called = false;
        try {
            $factory->withClient(
                'foo',
                new ClusterCredentials(clientKey: 'privateKey', token: sys_get_temp_dir() . '/' . $tokenFile),
                function (KubClient $client) use (&$called): void {
                    $called = true;
                },
            );

            $this->fail('A token designating a file must be refused');
        } catch (TokenFileNotAllowedException) {
        } finally {
            $tempFilesystem->delete($tokenFile);
        }

        $this->assertFalse($called);
        $this->assertSame([], $filesystem->listContents('', false)->toArray());
    }

    public function testInvokeAcceptsATokenDesignatingAFileWhenAllowed(): void
    {
        $tokenFile = 'east-paas-test-token-' . bin2hex(random_bytes(8));
        $tempFilesystem = $this->buildTempFilesystem();
        $tempFilesystem->write($tokenFile, 'token');

        try {
            $factory = $this->buildFactory(allowTokenFile: true);

            $this->assertInstanceOf(
                KubClient::class,
                $factory('foo', new ClusterCredentials(token: sys_get_temp_dir() . '/' . $tokenFile)),
            );
        } finally {
            $tempFilesystem->delete($tokenFile);
        }
    }
}
