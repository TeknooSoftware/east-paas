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

namespace Teknoo\East\Paas\Infrastructures\Kubernetes;

use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Traversable;

use function ksort;

/**
 * Collection of transcribers browsable by the Kubernetes driver to transcribe
 * a CompiledDeployment instance to Kubernetes manifests.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class TranscriberCollection implements TranscriberCollectionInterface
{
    /**
     * @var array<int, array<int, TranscriberInterface>>
     */
    private array $transcribers = [];

    public function add(int $priority, TranscriberInterface $transcriber): self
    {
        $this->transcribers[$priority][] = $transcriber;

        return $this;
    }

    public function getIterator(): Traversable
    {
        $orderedTranscriber = $this->transcribers;
        ksort($orderedTranscriber);
        foreach ($orderedTranscriber as &$transcribers) {
            foreach ($transcribers as $transcriber) {
                yield $transcriber;
            }
        }
    }
}
