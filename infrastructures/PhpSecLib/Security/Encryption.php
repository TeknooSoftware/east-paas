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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@$lengthdeloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Infrastructures\PhpSecLib\Security;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use SensitiveParameter;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\UnsupportedAlgorithmException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\WrongLibraryAPIException;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function base64_decode;
use function base64_encode;
use function method_exists;
use function strlen;
use function substr;

/**
 * Service build on PhpSecLib able to encrypt and decrypt content between servers, agents and workers to keep secrets
 *  credentials and all others confidential data.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Encryption implements EncryptionInterface
{
    public function __construct(
        private readonly PublicKey $publicKey,
        private readonly ?PrivateKey $privateKey,
        private readonly string $algorithm,
    ) {
    }

    /**
     * @param callable(string): string $method
     */
    private function processContent(
        callable $method,
        string $content,
        PrivateKey|PublicKey $key,
        int $coef = 1,
    ): string {
        if (!method_exists($key, 'getLength')) {
            return $method($content);
        }

        /** @var int $length */
        $length = $key->getLength();
        $bytesLength = $length / (8 * $coef) - (($coef - 1) * 2);
        $contentLength = strlen($content);

        $final = '';
        for ($i = 0; $i < $contentLength; $i += $bytesLength) {
            $final .= $method(
                substr(
                    string: $content,
                    offset: $i,
                    length: $bytesLength
                )
            );
        }

        return $final;
    }

    public function encrypt(
        #[SensitiveParameter] SensitiveContentInterface $data,
        PromiseInterface $promise,
        bool $returnBase64 = false,
    ): EncryptionInterface {
        if (!method_exists($this->publicKey, 'encrypt')) {
            $promise->fail(
                new WrongLibraryAPIException('PHPSecLib key as not encrypt capacity')
            );

            return $this;
        }

        try {
            $encryptedContent = $this->processContent(
                method: $this->publicKey->encrypt(...),
                content: $data->getContent(),
                key: $this->publicKey,
                coef: 2,
            );
        } catch (Throwable $error) {
            $promise->fail($error);

            return $this;
        }

        if ($returnBase64) {
            $encryptedContent = base64_encode($encryptedContent);
        }

        $promise->success($data->cloneWith(
            content: $encryptedContent,
            encryptionAlgorithm: $this->algorithm,
        ));

        return $this;
    }

    public function decrypt(
        #[SensitiveParameter] SensitiveContentInterface $data,
        PromiseInterface $promise,
        bool $isBase64 = false,
    ): EncryptionInterface {
        if (!$this->privateKey instanceof PrivateKey || !method_exists($this->privateKey, 'decrypt')) {
            $promise->fail(
                new WrongLibraryAPIException('PHPSecLib key as not decrypt capacity')
            );

            return $this;
        }

        if ($this->algorithm !== ($algo = $data->getEncryptionAlgorithm())) {
            if (empty($algo)) {
                $content = "This agent requires encryption in content, but this content is not encrypted";
            } elseif (empty($this->algorithm)) {
                $content = "This agent does not support encryption in content, but this content is encrypted";
            } else {
                $content = "$algo is not supported by this current configuration of this encryption service";
            }

            $promise->fail(new UnsupportedAlgorithmException(message: $content));

            return $this;
        }

        $encryptedContent = $data->getContent();
        if ($isBase64) {
            $encryptedContent = base64_decode($encryptedContent);
        }

        try {
            $decryptedContent = $this->processContent(
                method: $this->privateKey->decrypt(...),
                content: $encryptedContent,
                key: $this->privateKey,
            );
        } catch (Throwable $error) {
            $promise->fail($error);

            return $this;
        }

        $promise->success(
            $data->cloneWith(
                content: $decryptedContent,
                encryptionAlgorithm: null,
            )
        );

        return $this;
    }
}
