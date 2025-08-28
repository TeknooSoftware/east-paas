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

namespace Teknoo\Tests\East\Paas\Behat\Handler\Psr11;

use Override;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\JobDoneHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Psr11\JobDoneHandler as Original;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;
use Teknoo\Tests\East\Paas\Behat\FeatureContext;

use function is_array;
use function json_decode;

/**
 * Message handler for Symfony Messenger to handle a JobDone and forward it to a remmote server via a HTTP request.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[AsMessageHandler]
class JobDoneHandler extends Original
{
    #[Override]
    public function __invoke(JobDone $jobDone): JobDoneHandlerInterface
    {
        FeatureContext::$messageByTypeIsEncrypted[JobDone::class] = !is_array(
            json_decode(
                $jobDone->getMessage(),
                associative: true
            )
        )
            || (FeatureContext::$messageByTypeIsEncrypted[JobDone::class] ?? false);

        parent::__invoke($jobDone);

        return $this;
    }
}
