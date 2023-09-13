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

namespace Teknoo\East\Paas\Contracts\Security;

use Teknoo\East\Paas\Contracts\Message\MessageInterface;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * To define a service able to encrypt and decrypt message between servers, agents and workers to keep secrets
 * credentials and all others confidential data.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
interface EncryptionInterface
{
    /**
     * @param PromiseInterface<MessageInterface, mixed> $promise
     */
    public function encrypt(
        MessageInterface $data,
        PromiseInterface $promise,
    ): EncryptionInterface;

    /**
     * @param PromiseInterface<MessageInterface, mixed> $promise
     */
    public function decrypt(
        MessageInterface $data,
        PromiseInterface $promise,
    ): EncryptionInterface;
}
