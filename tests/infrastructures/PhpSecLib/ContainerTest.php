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

namespace Teknoo\Tests\East\Paas\Infrastructures\PhpSecLib;

use DI\Container;
use DI\ContainerBuilder;
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContainerTest extends TestCase
{
    private string $privateKey = __DIR__ . '/../../var/keys/private.pem';
    private string $publicKey = __DIR__ . '/../../var/keys/public.pem';

    protected function setUp(): void
    {
        $pk = RSA::createKey(1024);
        file_put_contents($this->privateKey, $pk->toString('PKCS8'));
        file_put_contents($this->publicKey, $pk->getPublicKey()->toString('PKCS8'));
    }

    /**
     * @return Container
     * @throws \Exception
     */
    protected function buildContainer() : Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(__DIR__ . '/../../../infrastructures/PhpSecLib/di.php');

        return $containerDefinition->build();
    }

    public function testGetEncryptionWhenNoKeysDefined()
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$algoEnvKey])) {
            unset($_ENV[$algoEnvKey]);
        }

        if (isset($_ENV[$privateKeyEnvKey])) {
            unset($_ENV[$privateKeyEnvKey]);
        }

        if (isset($_ENV[$privateKeyPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyPassphraaseEnvKey]);
        }

        if (isset($_ENV[$publicKeyEnvKey])) {
            unset($_ENV[$publicKeyEnvKey]);
        }

        self::assertNull(
            $container->get(EncryptionInterface::class),
        );
    }

    public function testGetEncryptionWhenNoPrivateKeyDefined()
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$algoEnvKey])) {
            unset($_ENV[$algoEnvKey]);
        }

        if (isset($_ENV[$privateKeyEnvKey])) {
            unset($_ENV[$privateKeyEnvKey]);
        }

        if (isset($_ENV[$privateKeyPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyPassphraaseEnvKey]);
        }

        $_ENV[$publicKeyEnvKey] = __DIR__ . '/../../var/keys/public.pem';

        $service = $container->get(EncryptionInterface::class);
        self::assertInstanceOf(EncryptionInterface::class, $service);

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects(self::once())->method('fail');

        $service->decrypt(
            $this->createMock(SensitiveContentInterface::class),
            $promise,
        );
    }

    public function testGetEncryptionWhenNoPublicKeyDefined()
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$algoEnvKey])) {
            unset($_ENV[$algoEnvKey]);
        }

        if (isset($_ENV[$privateKeyPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyPassphraaseEnvKey]);
        }

        if (isset($_ENV[$publicKeyEnvKey])) {
            unset($_ENV[$publicKeyEnvKey]);
        }

        $_ENV[$privateKeyEnvKey] = __DIR__ . '/../../var/keys/private.pem';

        $this->expectException(DIException::class);
        $container->get(EncryptionInterface::class);
    }

    public function testGetEncryptionWhenWrongAlgoDefined()
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        $_ENV[$algoEnvKey] = 'foo';

        if (isset($_ENV[$privateKeyPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyPassphraaseEnvKey]);
        }

        $_ENV[$publicKeyEnvKey] = __DIR__ . '/../../var/keys/public.pem';
        $_ENV[$privateKeyEnvKey] = __DIR__ . '/../../var/keys/private.pem';

        $this->expectException(DIException::class);
        $container->get(EncryptionInterface::class);
    }

    public function testGetEncryption()
    {
        $container = $this->buildContainer();

        $algoEnvKey = $container->get('teknoo.east.paas.seclib.algorithm.env_key');
        $privateKeyEnvKey = $container->get('teknoo.east.paas.seclib.private_key.env_key');
        $privateKeyPassphraaseEnvKey = $container->get('teknoo.east.paas.seclib.private_key_passphrase.env_key');
        $publicKeyEnvKey = $container->get('teknoo.east.paas.seclib.public_key.env_key');

        if (isset($_ENV[$privateKeyPassphraaseEnvKey])) {
            unset($_ENV[$privateKeyPassphraaseEnvKey]);
        }

        $_ENV[$algoEnvKey] = Algorithm::RSA->value;
        $_ENV[$publicKeyEnvKey] = __DIR__ . '/../../var/keys/public.pem';
        $_ENV[$privateKeyEnvKey] = __DIR__ . '/../../var/keys/private.pem';

        self::assertInstanceOf(
            EncryptionInterface::class,
            $container->get(EncryptionInterface::class)
        );
    }
}
