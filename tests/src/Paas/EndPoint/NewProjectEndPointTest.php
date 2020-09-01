<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Tests\EndPoint;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Paas\EndPoint\NewProjectEndPoint;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ProjectType;
use Teknoo\East\Foundation\Template\ResultInterface;
use Teknoo\East\Website\Loader\LoaderInterface;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Service\FindSlugService;
use Teknoo\East\WebsiteBundle\AdminEndPoint\AdminNewEndPoint;
use Teknoo\East\WebsiteBundle\Form\Type\TypeType;
use Teknoo\East\Paas\Loader\AccountLoader;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\Tests\East\WebsiteBundle\AdminEndPoint\AdminNewEndPointTest;

/**
 * @covers \Teknoo\East\Paas\EndPoint\NewProjectEndPoint
 */
class NewProjectEndPointTest extends AdminNewEndPointTest
{
    /**
     * @var AccountLoader
     */
    private $accountLoader;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AccountLoader
     */
    public function getAccountLoaderMock(): AccountLoader
    {
        if (!$this->accountLoader instanceof AccountLoader) {
            $this->accountLoader = $this->createMock(AccountLoader::class);
        }

        return $this->accountLoader;
    }

    /**
     * @return NewProjectEndPoint
     */
    public function buildEndPoint(string $formClass = ProjectType::class, string $objectClass = Project::class): NewProjectEndPoint
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects(self::any())->method('withHeader')->willReturnSelf();
        $response->expects(self::any())->method('withBody')->willReturnSelf();
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->expects(self::any())->method('createResponse')->willReturn($response);

        $stream = $this->createMock(StreamInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->expects(self::any())->method('createStream')->willReturn($stream);
        $streamFactory->expects(self::any())->method('createStreamFromFile')->willReturn($stream);
        $streamFactory->expects(self::any())->method('createStreamFromResource')->willReturn($stream);

        return (new NewProjectEndPoint())
            ->setWriter($this->getWriterService())
            ->setTemplating($this->getEngine())
            ->setRouter($this->getRouter())
            ->setFormFactory($this->getFormFactory())
            ->setFormClass($formClass)
            ->setObjectClass($objectClass)
            ->setResponseFactory($responseFactory)
            ->setStreamFactory($streamFactory)
            ->setLoader($this->createMock(LoaderInterface::class))
            ->setAccountLoader($this->getAccountLoaderMock())
            ->setViewPath('foo:bar.html.engine');
    }

    public function testSetAccountLoaderBadAccountLoader()
    {
        $this->expectException(\TypeError::class);
        $this->buildEndPoint()->setAccountLoader(new \stdClass());
    }

    public function testSetObjectClassBadArg()
    {
        $this->expectException(\TypeError::class);
        $this->buildEndPoint()->setObjectClass(new \stdClass());
    }

    public function testSetObjectClassBadClass()
    {
        $this->expectException(\LogicException::class);
        $this->buildEndPoint()->setObjectClass('fooBardd');
    }

    public function testInvokeAccountNotFound()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::never())->method('acceptResponse');
        $client->expects(self::once())->method('errorInRequest');

        $this->getFormFactory()
            ->expects(self::never())
            ->method('create');

        $this->getWriterService()
            ->expects(self::never())
            ->method('save');

        $this->getRouter()
            ->expects(self::never())
            ->method('generate');

        $this->getAccountLoaderMock()
            ->expects(self::once())
            ->method('load')
            ->willReturnCallback(function ($filter, PromiseInterface $promise) {
                self::assertEquals('projectIdValue', $filter);
                $promise->fail(new \DomainException());

                return $this->getAccountLoaderMock();
            });

        self::assertInstanceOf(
            NewProjectEndPoint::class,
            ($this->buildEndPoint())($request, $client, 'foo', 'projectIdValue')
        );
    }

    public function testSetFormOptions()
    {
        self::assertInstanceOf(
            NewProjectEndPoint::class,
            $this->buildEndPoint()
                ->setFormOptions(['doctrine_type' => ChoiceType::class])
        );
    }

    public function testInvokeNotSubmitted()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::once())->method('acceptResponse');
        $client->expects(self::never())->method('errorInRequest');

        $form = $this->createMock(FormInterface::class);
        $this->getFormFactory()
            ->expects(self::once())
            ->method('create')
            ->with(ProjectType::class)
            ->willReturn($form);

        $this->getWriterService()
            ->expects(self::never())
            ->method('save');

        $this->getRouter()
            ->expects(self::never())
            ->method('generate');

        $this->getAccountLoaderMock()
            ->expects(self::once())
            ->method('load')
            ->willReturnCallback(function ($filter, PromiseInterface $promise) {
                self::assertEquals('projectIdValue', $filter);
                $promise->success($this->createMock(Account::class));

                return $this->getAccountLoaderMock();
            });

        $this->getEngine()
            ->expects(self::any())
            ->method('render')
            ->willReturnCallback(function (PromiseInterface $promise) {
                $result = $this->createMock(ResultInterface::class);
                $result->expects(self::any())->method('__toString')->willReturn('foo');
                $promise->success($result);

                return $this->getEngine();
            });

        self::assertInstanceOf(
            NewProjectEndPoint::class,
            ($this->buildEndPoint())($request, $client, 'foo', 'projectIdValue')
        );
    }

    public function testInvokeSubmittedError()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::never())->method('acceptResponse');
        $client->expects(self::once())->method('errorInRequest');

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())->method('isSubmitted')->willReturn(true);
        $form->expects(self::any())->method('isValid')->willReturn(true);

        $this->getFormFactory()
            ->expects(self::once())
            ->method('create')
            ->with(ProjectType::class)
            ->willReturn($form);

        $this->getWriterService()
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function ($object, PromiseInterface $promise) {
                self::assertInstanceOf(Project::class, $object);
                $promise->fail(new \Exception());

                return $this->getWriterService();
            });

        $this->getRouter()
            ->expects(self::never())
            ->method('generate');

        $this->getAccountLoaderMock()
            ->expects(self::once())
            ->method('load')
            ->willReturnCallback(function ($filter, PromiseInterface $promise) {
                self::assertEquals('projectIdValue', $filter);
                $promise->success($this->createMock(Account::class));

                return $this->getAccountLoaderMock();
            });

        self::assertInstanceOf(
            NewProjectEndPoint::class,
            ($this->buildEndPoint())($request, $client, 'foo', 'projectIdValue')
        );
    }

    public function testInvokeSubmittedSuccess()
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $client = $this->createMock(ClientInterface::class);

        $client->expects(self::once())->method('acceptResponse');
        $client->expects(self::never())->method('errorInRequest');

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())->method('isSubmitted')->willReturn(true);
        $form->expects(self::any())->method('isValid')->willReturn(true);

        $this->getFormFactory()
            ->expects(self::once())
            ->method('create')
            ->with(ProjectType::class)
            ->willReturn($form);

        $this->getWriterService()
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(function ($object, PromiseInterface $promise) {
                self::assertInstanceOf(Project::class, $object);
                $promise->success($object);

                return $this->getWriterService();
            });

        $this->getRouter()
            ->expects(self::once())
            ->method('generate')
            ->with('foo')
            ->willReturn('bar');

        $this->getAccountLoaderMock()
            ->expects(self::once())
            ->method('load')
            ->willReturnCallback(function ($filter, PromiseInterface $promise) {
                self::assertEquals('projectIdValue', $filter);
                $promise->success($this->createMock(Account::class));

                return $this->getAccountLoaderMock();
            });

        self::assertInstanceOf(
            NewProjectEndPoint::class,
            ($this->buildEndPoint())($request, $client, 'foo', 'projectIdValue')
        );
    }

    public function testInvokeSubmittedSuccessWithSluggableObject()
    {
        $this->markTestSkipped();
    }
}
