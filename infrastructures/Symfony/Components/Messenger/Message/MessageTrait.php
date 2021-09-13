<?php

declare(strict_types=1);

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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message;

use Teknoo\Immutable\ImmutableTrait;

/**
 * Trait to implement messages to send on Symfony Messenger with all needed data to be processed by handler.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
trait MessageTrait
{
    use ImmutableTrait;

    private string $projectId;

    private string $environment;

    private string $jobId;

    private string $message;

    public function __construct(
        string $projectId,
        string $environment,
        string $jobId,
        string $message
    ) {
        $this->uniqueConstructorCheck();

        $this->projectId = $projectId;
        $this->environment = $environment;
        $this->jobId = $jobId;
        $this->message = $message;
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
