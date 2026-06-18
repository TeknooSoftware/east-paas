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

namespace Teknoo\Tests\East\Paas\Infrastructures\DockerCompose\Transcriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Container;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\Planning;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Job\SuccessCondition;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Accumulator;
use Teknoo\East\Paas\Infrastructures\DockerCompose\Transcriber\JobTranscriber;
use Teknoo\Recipe\Promise\PromiseInterface;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(JobTranscriber::class)]
class JobTranscriberTest extends TestCase
{
    private function buildTranscriber(): JobTranscriber
    {
        return new JobTranscriber();
    }

    private function buildJob(Planning $planning): Job
    {
        $pod = new Pod(
            name: 'migrate',
            replicas: 1,
            containers: [
                new Container('migrate', 'registry/php', '8.3', [], [], ['STEP' => 'db']),
            ],
        );

        return new Job(
            name: 'init',
            pods: ['migrate' => $pod],
            completionsCount: 2,
            isParallel: true,
            successCondition: new SuccessCondition([0], [1, 2]),
            timeLimit: 120,
            planning: $planning,
        );
    }

    public function testTranscribeDuringDeployment(): void
    {
        $job = $this->buildJob(Planning::DuringDeployment);

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd, $job): CompiledDeploymentInterface {
                $callback($job, [], [], 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->once())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        $services = $generation->getComposeFile()['services'];

        self::assertArrayHasKey('prj-init-migrate', $services);
        $service = $services['prj-init-migrate'];
        self::assertSame('registry/php:8.3', $service['image']);
        self::assertSame(['jobs'], $service['profiles']);
        self::assertSame('no', $service['restart']);
        self::assertSame(
            [
                'parallel' => true,
                'completions' => 2,
                'time_limit' => 120,
                'success_exit_codes' => [0],
                'failure_exit_codes' => [1, 2],
            ],
            $service['x-paas-job'],
        );
    }

    public function testTranscribeSkipsScheduled(): void
    {
        $job = $this->buildJob(Planning::Scheduled);

        $cd = $this->createMock(CompiledDeploymentInterface::class);
        $cd->expects($this->once())
            ->method('foreachJob')
            ->willReturnCallback(function (callable $callback) use ($cd, $job): CompiledDeploymentInterface {
                $callback($job, [], [], 'prj');

                return $cd;
            });

        $generation = new Accumulator('default-prj', 'private');

        $promise = $this->createMock(PromiseInterface::class);
        $promise->expects($this->never())->method('success');
        $promise->expects($this->never())->method('fail');

        $this->buildTranscriber()->transcribe(
            compiledDeployment: $cd,
            accumulator: $generation,
            promise: $promise,
            defaultsBag: $this->createStub(DefaultsBag::class),
            namespace: 'default',
        );

        self::assertSame([], $generation->getComposeFile());
    }
}
