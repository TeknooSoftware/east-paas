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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command;

use SensitiveParameter;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\JobDoneHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\JobDone;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Message handler for Symfony Messenger to handle a JobDone and print it to the standard output, in a
 * Symfony Console context
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DisplayResultHandler implements JobDoneHandlerInterface
{
    private ?OutputInterface $output = null;

    public function __construct(
        private ?EncryptionInterface $encryption,
    ) {
    }

    public function setOutput(?OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function __invoke(JobDone $jobDone): JobDoneHandlerInterface
    {
        if (null === $this->output) {
            return $this;
        }

        $processMessage = function (JobDone $jobDone): void {
            $this->output?->writeln($jobDone->getMessage());
        };

        if (null !== $this->encryption) {
            /** @var Promise<JobDone, mixed, mixed> $promise */
            $promise = new Promise(
                onSuccess: $processMessage,
                onFail: fn (#[SensitiveParameter] Throwable $error) => throw $error,
            );

            $this->encryption->decrypt(
                $jobDone,
                $promise,
            );
        } else {
            $processMessage($jobDone);
        }

        return $this;
    }
}
