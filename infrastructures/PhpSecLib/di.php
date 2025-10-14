<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\PhpSecLib;

use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\RSA;
use Psr\Container\ContainerInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Configuration\Algorithm;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\DIException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\InvalidConfigurationException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Security\Encryption;
use Throwable;

use function file_get_contents;
use function is_readable;

return [
    'teknoo.east.paas.seclib.algorithm.env_key' => 'TEKNOO_PAAS_SECURITY_ALGORITHM',
    'teknoo.east.paas.seclib.algorithm.default' => Algorithm::RSA->value,

    'teknoo.east.paas.seclib.private_key.env_key' => 'TEKNOO_PAAS_SECURITY_PRIVATE_KEY',
    'teknoo.east.paas.seclib.private_key_passphrase.env_key' => 'TEKNOO_PAAS_SECURITY_PRIVATE_KEY_PASSPHRASE',
    'teknoo.east.paas.seclib.public_key.env_key' => 'TEKNOO_PAAS_SECURITY_PUBLIC_KEY',

    EncryptionInterface::class => function (ContainerInterface $container): ?EncryptionInterface {
        try {
            $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
            $algoDefault = $container->get('teknoo.east.paas.seclib.algorithm.default');
            $privateKeyEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
            $privateKeyPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
            $publicKeyEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

            if (empty($_ENV[$privateKeyEnvKey]) && empty($_ENV[$publicKeyEnvKey])) {
                return null;
            }

            $algoValue = $_ENV[$algoEnvKey] ?? $algoDefault;
            $pkPassphrase = $_ENV[$privateKeyPassphraaseEnvKey] ?? null;
            $algo = Algorithm::from($algoValue);

            $privateKey = null;
            if (!empty($_ENV[$privateKeyEnvKey]) && is_readable($_ENV[$privateKeyEnvKey])) {
                $privateKContent = (string) file_get_contents($_ENV[$privateKeyEnvKey]);

                $privateKey = match ($algo) {
                    Algorithm::RSA => RSA::loadPrivateKey(
                        key: $privateKContent,
                        password: (string) $pkPassphrase,
                    ),
                    Algorithm::DSA => DSA::loadPrivateKey(
                        key: $privateKContent,
                        password: (string) $pkPassphrase,
                    )
                };
            }

            if (empty($_ENV[$publicKeyEnvKey]) || !is_readable($_ENV[$publicKeyEnvKey])) {
                throw new InvalidConfigurationException("The public key defined for East PaaS is not readable");
            }

            $publicKContent = (string) file_get_contents($_ENV[$publicKeyEnvKey]);

            $publicKey = match ($algo) {
                Algorithm::RSA => RSA::loadPublicKey($publicKContent),
                Algorithm::DSA => DSA::loadPublicKey($publicKContent),
            };

            return new Encryption(
                publicKey: $publicKey,
                privateKey: $privateKey,
                algorithm: $algo->value,
            );
        } catch (Throwable $error) {
            throw new DIException(
                message: 'Unable to load security configuration',
                code: 500,
                previous: $error,
            );
        }
    },
];
