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

namespace Teknoo\Tests\East\Paas\Infrastructures\PhpSecLib;

use DI\Container;
use DI\ContainerBuilder;
use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\RSA;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\SendHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Job\SendJobInterface;
use Teknoo\East\Paas\Contracts\Response\ErrorFactoryInterface;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\History\SendHistory;
use Teknoo\East\Paas\Infrastructures\Laminas\Recipe\Step\Job\SendJob;
use Teknoo\East\Paas\Infrastructures\Laminas\Response\ErrorFactory;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Configuration\Algorithm;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\DIException;
use Teknoo\East\Paas\Infrastructures\PhpSecLib\Exception\InvalidConfigurationException;
use Teknoo\Recipe\Promise\PromiseInterface;

use function file_put_contents;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    private string $privateKeyRSA = __DIR__ . '/../../var/keys/rsa.private.pem';

    private string $publicKeyRSA = __DIR__ . '/../../var/keys/rsa.public.pem';

    private string $privateKeyDSA = __DIR__ . '/../../var/keys/dsa.private.pem';

    private string $publicKeyDSA = __DIR__ . '/../../var/keys/dsa.public.pem';

    protected function setUp(): void
    {
        $pk = RSA::createKey(1024);
        file_put_contents($this->privateKeyRSA, $pk->toString('PKCS8'));
        file_put_contents($this->publicKeyRSA, $pk->getPublicKey()->toString('PKCS8'));

        $pk = DSA::createKey();
        file_put_contents($this->privateKeyDSA, $pk->toString('PKCS8'));
        file_put_contents($this->publicKeyDSA, $pk->getPublicKey()->toString('PKCS8'));
    }

    /**
     * @throws \Exception
     */
    protected function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__ . '/../../../infrastructures/PhpSecLib/di.php');

        return $containerDefinition->build();
    }

    public function testGetEncryptionWhenNoKeysDefined(): void
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyRSAPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$algoEnvKey])) {
            unset($_ENV[$algoEnvKey]);
        }

        if (isset($_ENV[$privateKeyRSAEnvKey])) {
            unset($_ENV[$privateKeyRSAEnvKey]);
        }

        if (isset($_ENV[$privateKeyRSAPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyRSAPassphraaseEnvKey]);
        }

        if (isset($_ENV[$publicKeyRSAEnvKey])) {
            unset($_ENV[$publicKeyRSAEnvKey]);
        }

        $this->assertNull($container->get(EncryptionInterface::class));
    }

    public function testGetEncryptionWhenNoPrivateKeyDefined(): void
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyRSAPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$algoEnvKey])) {
            unset($_ENV[$algoEnvKey]);
        }

        if (isset($_ENV[$privateKeyRSAEnvKey])) {
            unset($_ENV[$privateKeyRSAEnvKey]);
        }

        if (isset($_ENV[$privateKeyRSAPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyRSAPassphraaseEnvKey]);
        }

        $_ENV[$publicKeyRSAEnvKey] = __DIR__ . '/../../var/keys/rsa.public.pem';

        $service = $container->get(EncryptionInterface::class);
        $this->assertInstanceOf(EncryptionInterface::class, $service);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('fail');

        $service->decrypt(
            $this->createMock(SensitiveContentInterface::class),
            $promise,
        );
    }

    public function testGetEncryptionWhenNoPublicKeyDefined(): void
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyRSAPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$algoEnvKey])) {
            unset($_ENV[$algoEnvKey]);
        }

        if (isset($_ENV[$privateKeyRSAPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyRSAPassphraaseEnvKey]);
        }

        if (isset($_ENV[$publicKeyRSAEnvKey])) {
            unset($_ENV[$publicKeyRSAEnvKey]);
        }

        $_ENV[$privateKeyRSAEnvKey] = __DIR__ . '/../../var/keys/rsa.private.pem';

        $this->expectException(DIException::class);
        $container->get(EncryptionInterface::class);
    }

    public function testGetEncryptionWhenWrongAlgoDefined(): void
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyRSAPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        $_ENV[$algoEnvKey] = 'foo';

        if (isset($_ENV[$privateKeyRSAPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyRSAPassphraaseEnvKey]);
        }

        $_ENV[$publicKeyRSAEnvKey] = __DIR__ . '/../../var/keys/public.pem';
        $_ENV[$privateKeyRSAEnvKey] = __DIR__ . '/../../var/keys/private.pem';

        $this->expectException(DIException::class);
        $container->get(EncryptionInterface::class);
    }

    public function testGetEncryptionWithRSA(): void
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyRSAPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyRSAEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$privateKeyRSAPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyRSAPassphraaseEnvKey]);
        }

        $_ENV[$algoEnvKey] = Algorithm::RSA->value;
        $_ENV[$publicKeyRSAEnvKey] = __DIR__ . '/../../var/keys/rsa.public.pem';
        $_ENV[$privateKeyRSAEnvKey] = __DIR__ . '/../../var/keys/rsa.private.pem';

        $this->assertInstanceOf(EncryptionInterface::class, $container->get(EncryptionInterface::class));
    }

    public function testGetEncryptionWithDSA(): void
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyDSAEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyDSAPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyDSAEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$privateKeyDSAPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyDSAPassphraaseEnvKey]);
        }

        $_ENV[$algoEnvKey] = Algorithm::DSA->value;
        $_ENV[$publicKeyDSAEnvKey] = __DIR__ . '/../../var/keys/dsa.public.pem';
        $_ENV[$privateKeyDSAEnvKey] = __DIR__ . '/../../var/keys/dsa.private.pem';

        $this->assertInstanceOf(EncryptionInterface::class, $container->get(EncryptionInterface::class));
    }
}
