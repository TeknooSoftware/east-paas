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

namespace Teknoo\East\Paas\Infrastructures\DockerCompose\Value;

/**
 * Immutable description of a file the Docker Compose driver pushes to the host via the Ansible `copy` loops:
 * the relative source path (`src`), the destination path/filename on the host (`dest`) and an optional Unix
 * `mode`. Used by the accumulator's getFilesToCopy()/getCertificatesToCopy() in place of loose array shapes.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final readonly class FileToCopy
{
    public function __construct(
        public string $src,
        public string $dest,
        public ?string $mode = null,
    ) {
    }

    /**
     * Returns a copy with `src` resolved to an absolute path below the given working directory.
     */
    public function withResolvedSource(string $baseDir): self
    {
        return new self(
            src: $baseDir . '/' . $this->src,
            dest: $this->dest,
            mode: $this->mode,
        );
    }

    /**
     * Array form passed to the Ansible `copy` loop; `mode` is omitted when null.
     *
     * @return array{src: string, dest: string, mode?: string}
     */
    public function toArray(): array
    {
        $data = [
            'src' => $this->src,
            'dest' => $this->dest,
        ];

        if (null !== $this->mode) {
            $data['mode'] = $this->mode;
        }

        return $data;
    }
}
