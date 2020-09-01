<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Job;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Object\Job;
use Teknoo\East\Paas\Recipe\Step\Job\CreateNewJob;
use Teknoo\Recipe\ChefInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Job\CreateNewJob
 */
class CreateNewJobTest extends TestCase
{
    public function buildStep(): CreateNewJob
    {
        return new CreateNewJob();
    }

    public function testInvoke()
    {
        $chef = $this->createMock(ChefInterface::class);

        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['job' => new Job()]);

        self::assertInstanceOf(
            CreateNewJob::class,
            $this->buildStep()($chef)
        );
    }
}
