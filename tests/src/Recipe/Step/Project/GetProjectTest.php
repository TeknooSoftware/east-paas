<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\Tests\East\Paas\Recipe\Step\Project;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Client\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\Tests\East\Paas\ErrorFactory;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Step\Project\GetProject
 */
class GetProjectTest extends TestCase
{
    /**
     * @var ProjectLoader
     */
    private $projectLoader;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ProjectLoader
     */
    public function getProjectLoaderMock(): ProjectLoader
    {
        if (!$this->projectLoader instanceof ProjectLoader) {
            $this->projectLoader = $this->createMock(ProjectLoader::class);
        }

        return $this->projectLoader;
    }

    public function buildStep(): GetProject
    {
        return new GetProject(
            $this->getProjectLoaderMock(),
            new ErrorFactory(),
        );
    }

    public function testInvoke()
    {
        $chef = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);
        $project = $this->createMock(Project::class);

        $projectId = 'dev';

        $this->getProjectLoaderMock()
            ->expects(self::once())
            ->method('load')
            ->with($projectId)
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($project) {
                $promise->success($project);

                return $this->getProjectLoaderMock();
            });

        $chef->expects(self::once())
            ->method('updateWorkPlan')
            ->with(['project' => $project]);

        self::assertInstanceOf(
            GetProject::class,
            $this->buildStep()($projectId, $chef, $client)
        );
    }

    public function testInvokeFailureOnProjectLoading()
    {
        $chef = $this->createMock(ManagerInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $projectId = 'dev';
        $exception = new \DomainException();

        $this->getProjectLoaderMock()
            ->expects(self::once())
            ->method('load')
            ->with($projectId)
            ->willReturnCallback(function ($criteria, PromiseInterface $promise) use ($exception) {
                $promise->fail($exception);

                return $this->getProjectLoaderMock();
            });

        $client->expects(self::once())
            ->method('acceptResponse');

        $chef->expects(self::once())
            ->method('finish')
            ->with($exception);

        $chef->expects(self::never())
            ->method('updateWorkPlan');

        self::assertInstanceOf(
            GetProject::class,
            $this->buildStep()($projectId, $chef, $client)
        );
    }
}
