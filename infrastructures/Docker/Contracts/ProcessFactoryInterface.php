<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Docker\Contracts;

use Symfony\Component\Process\Process;

interface ProcessFactoryInterface
{
    /**
     * @param string[] $command
     * @return Process<mixed>
     */
    public function __invoke(array $command, string $cwd): Process;
}
