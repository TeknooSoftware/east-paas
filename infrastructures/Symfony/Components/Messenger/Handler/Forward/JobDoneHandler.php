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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Forward;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\JobDoneHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;

/**
 * Default message handler for Symfony Messenger to handle a JobDone and forward it to the defined handler.
 * If there are no defined handler, the message will be ignored, without throw any error.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[AsMessageHandler]
class JobDoneHandler
{
    private ?JobDoneHandlerInterface $handler = null;

    public function setHandler(?JobDoneHandlerInterface $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function __invoke(JobDone $jobDone): self
    {
        if (null !== $this->handler) {
            ($this->handler)($jobDone);
        }

        return $this;
    }
}
