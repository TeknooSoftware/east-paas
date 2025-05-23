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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Git;

use DateTime;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Symfony\Component\Process\Process;
use Teknoo\East\Paas\Contracts\Workspace\Visibility;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent\Generator;
use Teknoo\East\Paas\Infrastructures\Git\CloningAgent\Running;
use Teknoo\East\Paas\Infrastructures\Git\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Contracts\Object\SourceRepositoryInterface;
use Teknoo\East\Paas\Object\SshIdentity;
use Teknoo\East\Paas\Contracts\Repository\CloningAgentInterface;
use Teknoo\East\Paas\Contracts\Workspace\FileInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use TypeError;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Running::class)]
#[CoversClass(Generator::class)]
#[CoversClass(CloningAgent::class)]
class CloningAgentTest extends TestCase
{
    /**
     * @var ProcessFactoryInterface
     */
    private $processFactory;

    /**
     * @var Process
     */
    private $process;

    public function getProcessFactoryMock(bool $isSuccessFull = true): MockObject&ProcessFactoryInterface
    {
        if (!$this->processFactory instanceof ProcessFactoryInterface) {
            $this->processFactory = $this->createMock(ProcessFactoryInterface::class);
            $this->processFactory
                ->expects($this->any())
                ->method('__invoke')
                ->willReturn($this->getProcessMock());

            $this->getProcessMock()
                ->expects($this->any())
                ->method('isSuccessFul')
                ->willReturn($isSuccessFull);
        }

        return $this->processFactory;
    }

    public function getProcessMock(): MockObject&Process
    {
        if (!$this->process instanceof Process) {
            $this->process = $this->createMock(Process::class);
        }

        return $this->process;
    }

    /**
     * @return CloningAgent
     */
    public function buildAgent(): CloningAgent
    {
        return new CloningAgent(
            $this->getProcessFactoryMock(),
            'private.key',
        );
    }

    public function testConfigureBadRepository()
    {
        $this->expectException(TypeError::class);
        $this->buildAgent()
            ->configure(
                new stdClass(),
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
        $this->expectException(TypeError::class);

        $repository = $this->createMock(GitRepository::class);
        $repository->expects($this->any())->method('getIdentity')->willReturn(
            $this->createMock(SshIdentity::class)
        );

        $this->buildAgent()
            ->configure(
                $repository,
                new stdClass()
            );
    }

    public function testConfigure(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->expects($this->any())->method('getIdentity')->willReturn(
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
        $identity->expects($this->any())->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->expects($this->any())->method('getIdentity')->willReturn(
            $identity
        );
        $repository->expects($this->any())->method('getPullUrl')->willReturn(
            'git@foo:bar'
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects($this->once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace, $pk) {
                self::assertEquals('private.key', $file->getName());
                self::assertEquals('fooBar', $file->getContent());
                self::assertEquals(Visibility::Private, $file->getVisibility());
                
                $return('/foo/bar/private.key');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects($this->once())
            ->method('setWorkingDirectory');

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        $workspace->expects($this->once())
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
        $repository->expects($this->any())->method('getIdentity')->willReturn(null);
        $repository->expects($this->any())->method('getPullUrl')->willReturn(
            'https://foo.bar'
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects($this->never())
            ->method('writeFile');

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $return) use ($workspace) {
                $return('/foo/bar/repo', '/foo/bar/');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects($this->once())
            ->method('setWorkingDirectory');

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        $workspace->expects($this->once())
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
        $this->expectException(RuntimeException::class);

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
        $repository->expects($this->any())->method('getIdentity')->willReturn(
            $identity
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);
        $workspace->expects($this->never())
            ->method('prepareRepository');

        $workspace->expects($this->never())
            ->method('writeFile');

        $this->getProcessMock()
            ->expects($this->never())
            ->method('setWorkingDirectory');

        $this->getProcessMock()
            ->expects($this->never())
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
        $this->expectException(TypeError::class);
        $this->buildAgent()->cloningIntoPath(new DateTime());
    }

    public function testCloningIntoPathWithGenerator()
    {
        $this->expectException(RuntimeException::class);

        $agent = $this->buildAgent();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath($path = 'foo', 'bar')
        );
    }

    public function testCloningIntoPathWithHttp()
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->expects($this->any())->method('getIdentity')->willReturn(null);
        $repository->expects($this->any())->method('getPullUrl')->willReturn(
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
        $repository->expects($this->any())->method('getIdentity')->willReturn(null);
        $repository->expects($this->any())->method('getPullUrl')->willReturn(
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
        $identity->expects($this->any())->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->expects($this->any())->method('getIdentity')->willReturn(
            $identity
        );
        $repository->expects($this->any())->method('getPullUrl')->willReturn(
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
        $identity->expects($this->any())->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->expects($this->any())->method('getIdentity')->willReturn(
            $identity
        );
        $repository->expects($this->any())->method('getPullUrl')->willReturn(
            $url = 'git@foo:bar'
        );

        $this->getProcessFactoryMock(false);

        $agent = $this->buildAgent();

        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        $this->expectException(RuntimeException::class);
        self::assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath('foo', '/bar')
        );
    }

    public function testClone()
    {
        $agent = $this->buildAgent();
        $agent2 = clone $agent;

        self::assertNotSame($agent, $agent2);
    }
}
