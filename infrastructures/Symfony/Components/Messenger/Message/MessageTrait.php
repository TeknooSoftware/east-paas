<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\East\Paas\Contracts\Security\SensitiveContentInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Trait to implement messages to send on Symfony Messenger with all needed data to be processed by handler.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
trait MessageTrait
{
    use ImmutableTrait;

    public function __construct(
        private readonly string $projectId,
        private readonly string $environment,
        private readonly string $jobId,
        private readonly string $message,
        private readonly ?string $encryptionAlgorithm = null,
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContent(): string
    {
        return $this->getMessage();
    }

    public function getEncryptionAlgorithm(): ?string
    {
        return $this->encryptionAlgorithm;
    }

    public function cloneWith(string $content, ?string $encryptionAlgorithm): SensitiveContentInterface
    {
        return new self(
            projectId: $this->getProjectId(),
            environment: $this->getEnvironment(),
            jobId: $this->getJobId(),
            message: $content,
            encryptionAlgorithm: $encryptionAlgorithm,
        );
    }

    public function __toString()
    {
        return $this->message;
    }
}
