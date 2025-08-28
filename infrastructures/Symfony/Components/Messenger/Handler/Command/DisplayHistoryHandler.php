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

namespace Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Handler\Command;

use SensitiveParameter;
use Symfony\Component\Console\Output\OutputInterface;
use Teknoo\East\Paas\Contracts\Security\EncryptionInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Contracts\Messenger\Handler\HistorySentHandlerInterface;
use Teknoo\East\Paas\Infrastructures\Symfony\Messenger\Message\HistorySent;
use Teknoo\Recipe\Promise\Promise;
use Throwable;

/**
 * Message handler for Symfony Messenger to handle a HistorySent and print it to the standard output, in a
 * Symfony Console context
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class DisplayHistoryHandler implements HistorySentHandlerInterface
{
    private ?OutputInterface $output = null;

    public function __construct(
        private readonly ?EncryptionInterface $encryption,
    ) {
    }

    public function setOutput(?OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function __invoke(HistorySent $historySent): HistorySentHandlerInterface
    {
        if (null === $this->output) {
            return $this;
        }

        $processMessage = function (HistorySent $historySent): void {
            $this->output?->writeln($historySent->getMessage());
        };

        if (null !== $this->encryption) {
            /** @var Promise<HistorySent, mixed, mixed> $promise */
            $promise = new Promise(
                onSuccess: $processMessage,
                onFail: fn (#[SensitiveParameter] Throwable $error) => throw $error,
            );

            $this->encryption->decrypt(
                $historySent,
                $promise,
            );
        } else {
            $processMessage($historySent);
        }

        return $this;
    }
}
