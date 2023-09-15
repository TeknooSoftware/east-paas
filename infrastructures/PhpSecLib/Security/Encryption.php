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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@$lengthdeloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Infrastructures\PhpSecLib\Security;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use Teknoo\East\Paas\Contracts\Message\MessageInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\UnsupportedAlgorithmException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\WrongLibraryAPIException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function method_exists;
use function strlen;
use function substr;

/**
 * Service build on PhpSecLib able to encrypt and decrypt message between servers, agents and workers to keep secrets
 *  credentials and all others confidential data.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Encryption implements EncryptionInterface
{
    public function __construct(
        private PublicKey $publicKey,
        private PrivateKey $privateKey,
        private string $alogirthm,
    ) {
    }

    private function processMessage(
        callable $method,
        string $message,
        PrivateKey|PublicKey $key,
        int $coef = 1,
    ): string {
        if (!method_exists($key, 'getLength')) {
            return $method($message);
        }

        $length = $key->getLength();
        $bytesLength = $length / (8 * $coef) - (($coef - 1) * 2);
        $messageLength = strlen($message);

        $final = '';
        for ($i = 0; $i < $messageLength; $i += $bytesLength) {
            $final .= $method(
                substr(
                    string: $message,
                    offset: $i,
                    length: $bytesLength
                )
            );
        }

        return $final;
    }

    public function encrypt(MessageInterface $data, PromiseInterface $promise,): EncryptionInterface
    {
        if (!method_exists($this->publicKey, 'encrypt')) {
            $promise->fail(
                new WrongLibraryAPIException('PHPSecLib key as not encrypt capacity')
            );

            return $this;
        }

        try {
            $encryptedMessage = $this->processMessage(
                method: $this->publicKey->encrypt(...),
                message: $data->getMessage(),
                key: $this->publicKey,
                coef: 2,
            );
        } catch (Throwable $error) {
            $promise->fail($error);

            return $this;
        }

        $promise->success($data->cloneWith(
            message: $encryptedMessage,
            encryptionAlgorithm: $this->alogirthm,
        ));

        return $this;
    }

    public function decrypt(MessageInterface $data, PromiseInterface $promise,): EncryptionInterface
    {
        if (!method_exists($this->privateKey, 'decrypt')) {
            $promise->fail(
                new WrongLibraryAPIException('PHPSecLib key as not decrypt capacity')
            );

            return $this;
        }

        if ($this->alogirthm !== ($algo = $data->getEncryptionAlgorithm())) {
            if (empty($algo)) {
                $message = "This agent requires encryption in message, but this message is not encrypted";
            } elseif (empty($this->alogirthm)) {
                $message = "This agent does not support encryption in message, but this message is encrypted";
            } else {
                $message = "$algo is not supported by this current configuration of this encryption service";
            }

            $promise->fail(new UnsupportedAlgorithmException(message: $message));

            return $this;
        }


        try {
            $decryptedMessage = $this->processMessage(
                method: $this->privateKey->decrypt(...),
                message: $data->getMessage(),
                key: $this->privateKey,
            );
        } catch (Throwable $error) {
            $promise->fail($error);

            return $this;
        }

        $promise->success(
            $data->cloneWith(
                message: $decryptedMessage,
                encryptionAlgorithm: null,
            )
        );

        return $this;
    }
}
