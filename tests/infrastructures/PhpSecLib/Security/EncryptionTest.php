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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\PhpSecLib\Security;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\RSA;
use PHPUnit\Framework\Attributes\CoversClass;
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(WrongLibraryAPIException::class)]
#[CoversClass(UnsupportedAlgorithmException::class)]
#[CoversClass(Encryption::class)]
class EncryptionTest extends TestCase
{
    public function testEncryptWithBadAPI()
    {
        $privateKey = $this->createMock(PrivateKey::class);
        $publicKey = $this->createMock(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error) => $error instanceof WrongLibraryAPIException));

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->encrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testEncrypt()
    {
        $privateKey = RSA::createKey(1024);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $privateKey->getPublicKey(),
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo) {
                    self::assertEquals('rsa', $algo);

                    return $this->createMock(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content) => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->encrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testEncryptWithBase64()
    {
        $privateKey = RSA::createKey(1024);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $privateKey->getPublicKey(),
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo) use ($privateKey) {
                    self::assertEquals('rsa', $algo);

                    self::assertEquals(
                        'foo',
                        $privateKey->decrypt(base64_decode($content)),
                    );

                    return $this->createMock(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content) => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->encrypt(
                data: $content,
                promise: $promise,
                returnBase64: true,
            )
        );
    }

    public function testEncryptWithoutLength()
    {
        $privateKey = RSA::createKey(1024);

        $publicKey = new class implements PublicKey {
            public function encrypt($plaintext): string {
                return 'foo';
            }
            public function verify($content, $signature) {}
            public function toString($type, array $options = []) {}
            public function getFingerprint($algorithm) {}
        };

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo) {
                    self::assertEquals('rsa', $algo);

                    return $this->createMock(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content) => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->encrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testEncryptWithtError()
    {
        $privateKey = RSA::createKey(1024);

        $publicKey = new class implements PublicKey {
            public function encrypt($plaintext): string {
                throw new RuntimeException('foo');
            }
            public function verify($content, $signature) {}
            public function toString($type, array $options = []) {}
            public function getFingerprint($algorithm) {}
        };

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->never())
            ->method('cloneWith');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->encrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testDecryptWithBadAPI()
    {
        $privateKey = $this->createMock(PrivateKey::class);
        $publicKey = $this->createMock(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error) => $error instanceof WrongLibraryAPIException));

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testDecryptWithMismatchAlgo()
    {
        $privateKey = RSA::createKey(1024);
        $publicKey = $this->createMock(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('foo');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error) => $error instanceof UnsupportedAlgorithmException));

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testDecryptWithNotEncryptedContent()
    {
        $privateKey = RSA::createKey(1024);
        $publicKey = $this->createMock(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->any())
            ->method('getEncryptionAlgorithm')
            ->willReturn(null);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error) => $error instanceof UnsupportedAlgorithmException));

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testDecryptWithNotSupportedEncrypted()
    {
        $privateKey = RSA::createKey(1024);
        $publicKey = $this->createMock(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            alogirthm: '',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn('foo');
        $content->expects($this->any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('foo');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail')
            ->with($this->callback(fn ($error) => $error instanceof UnsupportedAlgorithmException));

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testDecrypt()
    {
        $privateKey = RSA::createKey(1024);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey = $privateKey->getPublicKey(),
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn($publicKey->encrypt('foo'));
        $content->expects($this->any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo) {
                    self::assertEmpty($algo);

                    return $this->createMock(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content) => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $content,
                promise: $promise,
            )
        );
    }

    public function testDecryptWithBase64()
    {
        $privateKey = RSA::createKey(1024);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey = $privateKey->getPublicKey(),
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn(base64_encode($publicKey->encrypt('foo')));
        $content->expects($this->any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');
        $content->expects($this->once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($content, $algo) {
                    self::assertEmpty($algo);

                    return $this->createMock(SensitiveContentInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())
            ->method('success')
            ->with($this->callback(fn ($content) => $content instanceof SensitiveContentInterface));
        $promise->expects($this->never())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $content,
                promise: $promise,
                isBase64: true,
            )
        );
    }

    public function testDecryptWithError()
    {
        $privateKey = RSA::createKey(1024);

        $privateKey2 = new class implements PrivateKey {
            public function decrypt($ciphertext): string {
                throw new RuntimeException('foo');
            }

            public function sign($content) {}
            public function getPublicKey() {}
            public function toString($type, array $options = []) {}
            public function withPassword($password = false) {}
        };

        $service = new Encryption(
            privateKey: $privateKey2,
            publicKey: $publicKey = $privateKey->getPublicKey(),
            alogirthm: 'rsa',
        );

        $content = $this->createMock(SensitiveContentInterface::class);
        $content->expects($this->any())
            ->method('getContent')
            ->willReturn($publicKey->encrypt('foo'));
        $content->expects($this->any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');
        $content->expects($this->never())
            ->method('cloneWith');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())
            ->method('success');
        $promise->expects($this->once())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $content,
                promise: $promise,
            )
        );
    }
}
