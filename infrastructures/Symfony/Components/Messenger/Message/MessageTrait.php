<?php

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\Immutable\ImmutableTrait;

/**
 * Trait to implement messages to send on Symfony Messenger with all needed data to be processed by handler.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait MessageTrait
{
    use ImmutableTrait;

    public function __construct(
        private readonly string $projectId,
        private readonly string $environment,
        private readonly string $jobId,
        private readonly string $message
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

    public function __toString()
    {
        return $this->message;
    }
}
