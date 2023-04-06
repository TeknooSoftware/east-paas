<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Git;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
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
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Paas\Infrastructures\Git\CloningAgent
 * @covers \Teknoo\East\Paas\Infrastructures\Git\CloningAgent\Generator
 * @covers \Teknoo\East\Paas\Infrastructures\Git\CloningAgent\Running
 */
class CloningAgentTest extends TestCase
{
    /**
     * @var Process
     */
    private $process;

    public function getProcessMock(bool $isSuccessFull = true): MockObject&Process
    {
        if (!$this->process instanceof Process) {
            $this->process = $this->createMock(Process::class);

            $this->process
                ->expects(self::any())
                ->method('isSuccessFul')
                ->willReturn($isSuccessFull);
        }

        return $this->process;
    }

    /**
     * @return CloningAgent
     */
    public function buildAgent(): CloningAgent
    {
        return new CloningAgent(
            $this->getProcessMock(),
            'private.key',
        );
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
        $this->expectException(LogicException::class);
        $this->buildAgent()
            ->configure(
                $this->createMock(SourceRepositoryInterface::class),
                $this->createMock(JobWorkspaceInterface::class)
            );
    }

    public function testConfigureGitRepositoryWithNoIdentity()
    {
        $this->expectException(LogicException::class);
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

    public function testRunWithSSH()
    {
        $identity = $this->createMock(SshIdentity::class);
        $identity->expects(self::any())->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(
            $identity
        );
        $repository->expects(self::any())->method('getPullUrl')->willReturn(
            'git@foo:bar'
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects(self::once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace, $pk) {
                self::assertEquals('private.key', $file->getName());
                self::assertEquals('fooBar', $file->getContent());
                self::assertEquals(Visibility::Private, $file->getVisibility());
                
                $return('/foo/bar/private.key');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects(self::once())
            ->method('setWorkingDirectory');

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        $workspace->expects(self::once())
            ->method('prepareRepository')
            ->willReturnCallback(
                function () use ($agent, $workspace) {
                    $agent->cloningIntoPath('/bar', '/foo');

                    return $workspace;
                }
            );

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->run()
        );
    }

    public function testRunWithHttps()
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(null);
        $repository->expects(self::any())->method('getPullUrl')->willReturn(
            'https://foo.bar'
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects(self::never())
            ->method('writeFile');

        $workspace->expects(self::once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $return) use ($workspace) {
                $return('/foo/bar/repo', '/foo/bar/');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects(self::once())
            ->method('setWorkingDirectory');

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        $workspace->expects(self::once())
            ->method('prepareRepository')
            ->willReturnCallback(
                function () use ($agent, $workspace) {
                    $agent->cloningIntoPath('/bar', '/foo');

                    return $workspace;
                }
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
        $this->expectException(LogicException::class);

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

        $this->getProcessMock()
            ->expects(self::never())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects(self::never())
            ->method('run');

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
            $agent->cloningIntoPath($path = 'foo', 'bar')
        );
    }

    public function testCloningIntoPathWithHttp()
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(null);
        $repository->expects(self::any())->method('getPullUrl')->willReturn(
            'http://foo.bar'
        );

        $agent = $this->buildAgent();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        $this->expectException(InvalidArgumentException::class);
        $agent->cloningIntoPath('foo', '/bar');
    }

    public function testCloningIntoPathWithHttps()
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(null);
        $repository->expects(self::any())->method('getPullUrl')->willReturn(
            'https://foo.bar'
        );

        $agent = $this->buildAgent();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath('foo', '/bar')
        );
    }

    public function testCloningIntoPathWithSSH()
    {
        $identity = $this->createMock(SshIdentity::class);
        $identity->expects(self::any())->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(
            $identity
        );
        $repository->expects(self::any())->method('getPullUrl')->willReturn(
            'git@foo.bar'
        );

        $agent = $this->buildAgent();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath('foo', '/bar')
        );
    }

    public function testCloningIntoPathErrorInExecution()
    {
        $identity = $this->createMock(SshIdentity::class);
        $identity->expects(self::any())->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->expects(self::any())->method('getIdentity')->willReturn(
            $identity
        );
        $repository->expects(self::any())->method('getPullUrl')->willReturn(
            $url = 'git@foo:bar'
        );

        $this->getProcessMock(false);

        $agent = $this->buildAgent();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        $this->expectException(\RuntimeException::class);
        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath('foo', '/bar')
        );
    }

    public function testClone()
    {
        $agent = $this->buildAgent();
        $agent2 = clone $agent;

        $rp = new \ReflectionProperty(CloningAgent::class, 'gitProcess');
        $rp->setAccessible(true);
        self::assertNotSame($this->getProcessMock(), $rp->getValue($agent2));
        self::assertSame($this->getProcessMock(), $rp->getValue($agent));
    }
}
