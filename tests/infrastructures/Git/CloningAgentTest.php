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

namespace Teknoo\Tests\East\Paas\Infrastructures\Git;

use Symplify\GitWrapper\GitWrapper;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\SshIdentity;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Git\CloningAgent
 * @covers \Teknoo\East\Paas\Infrastructures\Git\CloningAgent\Generator
 * @covers \Teknoo\East\Paas\Infrastructures\Git\CloningAgent\Running
 */
class CloningAgentTest extends TestCase
{
    /**
     * @var GitWrapper
     */
    private $gitWrapper;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|GitWrapper
     */
    public function getGitWrapperMock()
    {
        if (!$this->gitWrapper instanceof \PHPUnit\Framework\MockObject\MockObject) {
            $this->gitWrapper = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['setPrivateKey', 'cloneRepository'])
                ->getMock();
        }

        return $this->gitWrapper;
    }

    /**
     * @return CloningAgent
     */
    public function buildAgent(): CloningAgent
    {
        return new CloningAgent($this->getGitWrapperMock());
    }

    public function testMissingGitWrapper()
    {
        $this->expectException(\RuntimeException::class);
        $a =  new CloningAgent(null);
    }

    public function testConfigureBadRepository()
    {
        $this->expectException(\TypeError::class);
        $this->buildAgent()
            ->configure(
                new \stdClass(),
                $this->createMock(JobWorkspaceInterface::class)
            );
    }

    public function testConfigureNotGitRepository()
    {
        $this->expectException(\LogicException::class);
        $this->buildAgent()
            ->configure(
                $this->createMock(SourceRepositoryInterface::class),
                $this->createMock(JobWorkspaceInterface::class)
            );
    }

    public function testConfigureGitRepositoryWithNoIdentity()
    {
        $this->expectException(\LogicException::class);
        $this->buildAgent()
            ->configure(
                $this->createMock(GitRepository::class),
                $this->createMock(JobWorkspaceInterface::class)
            );
    }

    public function testConfigureWithBadWorkspace()
    {
        $this->expectException(\TypeError::class);

        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(
            $this->createMock(SshIdentity::class)
        );

        $this->buildAgent()
            ->configure(
                $repository,
                new \stdClass()
            );
    }

    public function testConfigure(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(
            $this->createMock(SshIdentity::class)
        );

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $this->buildAgent()
                ->configure(
                    $repository,
                    $this->createMock(JobWorkspaceInterface::class)
                )
        );
    }

    public function testRun()
    {
        $identity = $this->createMock(SshIdentity::class);
        $identity->expects(self::any())->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(
            $identity
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $workspace->expects(self::once())
            ->method('prepareRepository');

        $workspace->expects(self::once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace, $pk) {
                self::assertEquals('private.key', $file->getName());
                self::assertEquals('fooBar', $file->getContent());
                self::assertEquals(FileInterface::VISIBILITY_PRIVATE, $file->getVisibility());
                
                $return('/foo/bar/private.key');

                return $workspace;
            });

        $this->getGitWrapperMock()
            ->expects(self::once())
            ->method('setPrivateKey')
            ->with('/foo/bar/private.key');

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->run()
        );
    }

    public function testRunWithGenerator()
    {
        $this->expectException(\RuntimeException::class);

        $agent = $this->buildAgent();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->run()
        );
    }

    public function testRunWrongIdentityObject()
    {
        $this->expectException(\LogicException::class);

        $identity = $this->createMock(IdentityInterface::class);;

        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(
            $identity
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $workspace->expects(self::never())
            ->method('prepareRepository');

        $workspace->expects(self::never())
            ->method('writeFile');

        $this->getGitWrapperMock()
            ->expects(self::never())
            ->method('setPrivateKey');

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->run()
        );
    }

    public function testCloningIntoPathBadPath()
    {
        $this->expectException(\TypeError::class);
        $this->buildAgent()->cloningIntoPath(new \DateTime());
    }

    public function testCloningIntoPathWithGenerator()
    {
        $this->expectException(\RuntimeException::class);

        $agent = $this->buildAgent();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath($path = 'foo')
        );
    }
    public function testCloningIntoPath()
    {
        $identity = $this->createMock(SshIdentity::class);
        $identity->expects(self::any())->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(
            $identity
        );
        $repository->expects(self::any())->method('getPullUrl')->willReturn(
            $url = 'https://foo.bar'
        );


        $agent = $this->buildAgent();

        $this->getGitWrapperMock()
            ->expects(self::once())
            ->method('cloneRepository')
            ->with($url, $path = 'foo/bar');

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath($path)
        );
    }

    public function testClone()
    {
        $agent = $this->buildAgent();
        $agent2 = clone $agent;

        $rp = new \ReflectionProperty(CloningAgent::class, 'gitWrapper');
        $rp->setAccessible(true);
        self::assertNotSame($this->getGitWrapperMock(), $rp->getValue($agent2));
        self::assertSame($this->getGitWrapperMock(), $rp->getValue($agent));
    }
}
