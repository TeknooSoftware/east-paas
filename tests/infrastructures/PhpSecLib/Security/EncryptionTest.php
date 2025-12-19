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

namespace Teknoo\Tests\East\Paas\Infrastructures\PhpSecLib\Security;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\UnsupportedAlgorithmException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\WrongLibraryAPIException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Security\Encryption;
use Teknoo\Recipe\Promise\PromiseInterface;

use function base64_decode;
use function base64_encode;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(WrongLibraryAPIException::class)]
#[CoversClass(UnsupportedAlgorithmException::class)]
#[CoversClass(Encryption::class)]
class EncryptionTest extends TestCase
{
    public function testEncryptWithBadAPI(): void
    {
        $privateKey = $this->createStub(PrivateKey::class);
        $publicKey = $this->createStub(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            algorithm: 'rsa',
        );

        $content = $this->createStub(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error): bool => $error instanceof WrongLibraryAPIException));

        $this->assertInstanceOf(EncryptionInterface::class, $service->encrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testEncrypt(): void
    {
        $privateKey = RSA::createKey(1024);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $privateKey->getPublicKey(),
            algorithm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo): MockObject|Stub {
                    $this->assertEquals('rsa', $algo);

                    return $this->createStub(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content): bool => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        $this->assertInstanceOf(EncryptionInterface::class, $service->encrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testEncryptWithBase64(): void
    {
        $privateKey = RSA::createKey(1024);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $privateKey->getPublicKey(),
            algorithm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo) use ($privateKey): MockObject|Stub {
                    $this->assertEquals('rsa', $algo);

                    $this->assertEquals('foo', $privateKey->decrypt(base64_decode((string) $content)));

                    return $this->createStub(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content): bool => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        $this->assertInstanceOf(EncryptionInterface::class, $service->encrypt(
            data: $content,
            promise: $promise,
            returnBase64: true,
        ));
    }

    public function testEncryptWithoutLength(): void
    {
        $privateKey = RSA::createKey(1024);

        $publicKey = new class () implements PublicKey {
            public function encrypt($plaintext): string
            {
                return 'foo';
            }

            public function verify($content, $signature): void
            {
            }

            public function toString($type, array $options = []): void
            {
            }

            public function getFingerprint($algorithm): void
            {
            }
        };

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            algorithm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo): MockObject|Stub {
                    $this->assertEquals('rsa', $algo);

                    return $this->createStub(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content): bool => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        $this->assertInstanceOf(EncryptionInterface::class, $service->encrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testEncryptWithtError(): void
    {
        $privateKey = RSA::createKey(1024);

        $publicKey = new class () implements PublicKey {
            public function encrypt($plaintext): string
            {
                throw new RuntimeException('foo');
            }

            public function verify($content, $signature): void
            {
            }

            public function toString($type, array $options = []): void
            {
            }

            public function getFingerprint($algorithm): void
            {
            }
        };

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            algorithm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->never())
            ->method('cloneWith');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail');

        $this->assertInstanceOf(EncryptionInterface::class, $service->encrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testDecryptWithBadAPI(): void
    {
        $privateKey = $this->createStub(PrivateKey::class);
        $publicKey = $this->createStub(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            algorithm: 'rsa',
        );

        $content = $this->createStub(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');
        $content
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error): bool => $error instanceof WrongLibraryAPIException));

        $this->assertInstanceOf(EncryptionInterface::class, $service->decrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testDecryptWithMismatchAlgo(): void
    {
        $privateKey = RSA::createKey(1024);
        $publicKey = $this->createStub(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            algorithm: 'rsa',
        );

        $content = $this->createStub(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');
        $content
            ->method('getEncryptionAlgorithm')
            ->willReturn('foo');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error): bool => $error instanceof UnsupportedAlgorithmException));

        $this->assertInstanceOf(EncryptionInterface::class, $service->decrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testDecryptWithNotEncryptedContent(): void
    {
        $privateKey = RSA::createKey(1024);
        $publicKey = $this->createStub(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            algorithm: 'rsa',
        );

        $content = $this->createStub(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');
        $content
            ->method('getEncryptionAlgorithm')
            ->willReturn(null);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error): bool => $error instanceof UnsupportedAlgorithmException));

        $this->assertInstanceOf(EncryptionInterface::class, $service->decrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testDecryptWithNotSupportedEncrypted(): void
    {
        $privateKey = RSA::createKey(1024);
        $publicKey = $this->createStub(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            algorithm: '',
        );

        $content = $this->createStub(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn('foo');
        $content
            ->method('getEncryptionAlgorithm')
            ->willReturn('foo');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error): bool => $error instanceof UnsupportedAlgorithmException));

        $this->assertInstanceOf(EncryptionInterface::class, $service->decrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testDecrypt(): void
    {
        $privateKey = RSA::createKey(1024);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey = $privateKey->getPublicKey(),
            algorithm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn($publicKey->encrypt('foo'));
        $content
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo): MockObject|Stub {
                    $this->assertEmpty($algo);

                    return $this->createStub(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content): bool => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        $this->assertInstanceOf(EncryptionInterface::class, $service->decrypt(
            data: $content,
            promise: $promise,
        ));
    }

    public function testDecryptWithBase64(): void
    {
        $privateKey = RSA::createKey(1024);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey = $privateKey->getPublicKey(),
            algorithm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn(base64_encode((string) $publicKey->encrypt('foo')));
        $content
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo): MockObject|Stub {
                    $this->assertEmpty($algo);

                    return $this->createStub(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content): bool => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        $this->assertInstanceOf(EncryptionInterface::class, $service->decrypt(
            data: $content,
            promise: $promise,
            isBase64: true,
        ));
    }

    public function testDecryptWithError(): void
    {
        $privateKey = RSA::createKey(1024);

        $privateKey2 = new class () implements PrivateKey {
            public function decrypt($ciphertext): string
            {
                throw new RuntimeException('foo');
            }

            public function sign($content): void
            {
            }

            public function getPublicKey(): void
            {
            }

            public function toString($type, array $options = []): void
            {
            }

            public function withPassword($password = false): void
            {
            }
        };

        $service = new Encryption(
            privateKey: $privateKey2,
            publicKey: $publicKey = $privateKey->getPublicKey(),
            algorithm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content
            ->method('getContent')
            ->willReturn($publicKey->encrypt('foo'));
        $content
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');
        $content->expects($this->never())
            ->method('cloneWith');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail');

        $this->assertInstanceOf(EncryptionInterface::class, $service->decrypt(
            data: $content,
            promise: $promise,
        ));
    }
}
