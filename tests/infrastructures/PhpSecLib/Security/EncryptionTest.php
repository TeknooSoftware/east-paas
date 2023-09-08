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
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Message\MessageInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\UnsupportedAlgorithmException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\WrongLibraryAPIException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Security\Encryption;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\PhpSecLib\Security\Encryption
 * @covers \Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\UnsupportedAlgorithmException
 * @covers \Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\WrongLibraryAPIException
 */
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

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessage')
            ->willReturn('foo');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())
            ->method('success');
        $promise->expects(self::once())
            ->method('fail')
            ->with($this->callback(fn ($error) => $error instanceof WrongLibraryAPIException));

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->encrypt(
                data: $message,
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

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessage')
            ->willReturn('foo');
        $message->expects(self::once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($message, $algo) {
                    self::assertEquals('rsa', $algo);

                    return $this->createMock(MessageInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())
            ->method('success')
            ->with($this->callback(fn ($message) => $message instanceof MessageInterface));
        $promise->expects(self::never())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->encrypt(
                data: $message,
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

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessage')
            ->willReturn('foo');
        $message->expects(self::any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())
            ->method('success');
        $promise->expects(self::once())
            ->method('fail')
            ->with($this->callback(fn ($error) => $error instanceof WrongLibraryAPIException));

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $message,
                promise: $promise,
            )
        );
    }

    public function testDecryptWithBadAlgo()
    {
        $privateKey = RSA::createKey(1024);
        $publicKey = $this->createMock(PublicKey::class);

        $service = new Encryption(
            privateKey: $privateKey,
            publicKey: $publicKey,
            alogirthm: 'rsa',
        );

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessage')
            ->willReturn('foo');
        $message->expects(self::any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('foo');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::never())
            ->method('success');
        $promise->expects(self::once())
            ->method('fail')
            ->with($this->callback(fn ($error) => $error instanceof UnsupportedAlgorithmException));

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $message,
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

        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::any())
            ->method('getMessage')
            ->willReturn($publicKey->encrypt('foo'));
        $message->expects(self::any())
            ->method('getEncryptionAlgorithm')
            ->willReturn('rsa');
        $message->expects(self::once())
            ->method('cloneWith')
            ->willReturnCallback(
                function ($message, $algo) {
                    self::assertEmpty($algo);

                    return $this->createMock(MessageInterface::class);
                }
            );

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())
            ->method('success')
            ->with($this->callback(fn ($message) => $message instanceof MessageInterface));
        $promise->expects(self::never())
            ->method('fail');

        self::assertInstanceOf(
            EncryptionInterface::class,
            $service->decrypt(
                data: $message,
                promise: $promise,
            )
        );
    }
}
