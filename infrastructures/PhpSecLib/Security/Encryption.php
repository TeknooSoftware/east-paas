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

namespace Teknoo\East\Paas\Infrastructures\PhpSecLib\Security;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use Teknoo\East\Paas\Contracts\Message\MessageInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\UnsupportedAlgorithmException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\WrongLibraryAPIException;
use Teknoo\Recipe\Promise\PromiseInterface;

use function method_exists;

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

    public function encrypt(MessageInterface $data, PromiseInterface $promise,): EncryptionInterface
    {
        if (!method_exists($this->publicKey, 'encrypt')) {
            $promise->fail(
                new WrongLibraryAPIException('PHPSecLib key as not encrypt capacity')
            );

            return $this;
        }

        $encryptedMessage = $this->publicKey->encrypt($data->getMessage());

        $promise->success(
            $data->cloneWith(
                message: $encryptedMessage,
                encryptionAlgorithm: $this->alogirthm,
            )
        );

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
            $promise->fail(
                new UnsupportedAlgorithmException(
                    message: "$algo is not supported by this current configuration of this encryption service"
                )
            );

            return $this;
        }

        $decryptedMessage = $this->privateKey->decrypt($data->getMessage());

        $promise->success(
            $data->cloneWith(
                message: $decryptedMessage,
                encryptionAlgorithm: null,
            )
        );

        return $this;
    }
}
