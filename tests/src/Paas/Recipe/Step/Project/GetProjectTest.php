<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Step\Project;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Paas\Loader\ProjectLoader;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\East\Foundation\Promise\PromiseInterface;

/**
 * @covers \Teknoo\East\Paas\Recipe\Step\Project\GetProject
 * @covers \Teknoo\East\Paas\Recipe\Traits\ErrorTrait
 * @covers \Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait
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
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withAddedHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->expects(self::any())->method('createResponse')->willReturn(
            $response
        );

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->expects(self::any())->method('createStream')->willReturn(
            $this->createMock(StreamInterface::class)
        );

        return new GetProject(
            $this->getProjectLoaderMock(),
            $responseFactory,
            $streamFactory
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
