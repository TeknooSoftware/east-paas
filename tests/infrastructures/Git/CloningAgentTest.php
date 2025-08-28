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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Running::class)]
#[CoversClass(Generator::class)]
#[CoversClass(CloningAgent::class)]
class CloningAgentTest extends TestCase
{
    private (MockObject&ProcessFactoryInterface) | null $processFactory = null;

    private (MockObject&Process)|null $process = null;

    public function getProcessFactoryMock(bool $isSuccessFull = true): MockObject&ProcessFactoryInterface
    {
        if (!$this->processFactory instanceof ProcessFactoryInterface) {
            $this->processFactory = $this->createMock(ProcessFactoryInterface::class);
            $this->processFactory
                ->method('__invoke')
                ->willReturn($this->getProcessMock());

            $this->getProcessMock()
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

    public function buildAgent(): CloningAgent
    {
        return new CloningAgent(
            $this->getProcessFactoryMock(),
            'private.key',
        );
    }

    public function testConfigureBadRepository(): void
    {
        $this->expectException(TypeError::class);
        $this->buildAgent()
            ->configure(
                new stdClass(),
                $this->createMock(JobWorkspaceInterface::class)
            );
    }

    public function testConfigureNotGitRepository(): void
    {
        $this->expectException(LogicException::class);
        $this->buildAgent()
            ->configure(
                $this->createMock(SourceRepositoryInterface::class),
                $this->createMock(JobWorkspaceInterface::class)
            );
    }

    public function testConfigureGitRepositoryWithNoIdentity(): void
    {
        $this->expectException(LogicException::class);
        $this->buildAgent()
            ->configure(
                $this->createMock(GitRepository::class),
                $this->createMock(JobWorkspaceInterface::class)
            );
    }

    public function testConfigureWithBadWorkspace(): void
    {
        $this->expectException(TypeError::class);

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getIdentity')->willReturn(
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
        $repository->method('getIdentity')->willReturn(
            $this->createMock(SshIdentity::class)
        );

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $this->buildAgent()
                ->configure(
                    $repository,
                    $this->createMock(JobWorkspaceInterface::class)
                )
        );
    }

    public function testRunWithSSH(): void
    {
        $identity = $this->createMock(SshIdentity::class);
        $identity->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getIdentity')->willReturn(
            $identity
        );
        $repository->method('getPullUrl')->willReturn(
            'git@foo:bar'
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects($this->once())
            ->method('writeFile')
            ->willReturnCallback(function (FileInterface $file, callable $return) use ($workspace, $pk): MockObject {
                $this->assertEquals('private.key', $file->getName());
                $this->assertEquals('fooBar', $file->getContent());
                $this->assertEquals(Visibility::Private, $file->getVisibility());

                $return('/foo/bar/private.key');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects($this->once())
            ->method('setWorkingDirectory');

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        $workspace->expects($this->once())
            ->method('prepareRepository')
            ->willReturnCallback(
                function () use ($agent, $workspace): MockObject {
                    $agent->cloningIntoPath('/bar', '/foo');

                    return $workspace;
                }
            );

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent->run()
        );
    }

    public function testRunWithHttps(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getIdentity')->willReturn(null);
        $repository->method('getPullUrl')->willReturn(
            'https://foo.bar'
        );

        $agent = $this->buildAgent();
        $workspace = $this->createMock(JobWorkspaceInterface::class);

        $workspace->expects($this->never())
            ->method('writeFile');

        $workspace->expects($this->once())
            ->method('runInRepositoryPath')
            ->willReturnCallback(function (callable $return) use ($workspace): MockObject {
                $return('/foo/bar/repo', '/foo/bar/');

                return $workspace;
            });

        $this->getProcessMock()
            ->expects($this->once())
            ->method('setWorkingDirectory');

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        $workspace->expects($this->once())
            ->method('prepareRepository')
            ->willReturnCallback(
                function () use ($agent, $workspace): MockObject {
                    $agent->cloningIntoPath('/bar', '/foo');

                    return $workspace;
                }
            );

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent->run()
        );
    }

    public function testRunWithGenerator(): void
    {
        $this->expectException(RuntimeException::class);

        $agent = $this->buildAgent();

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent->run()
        );
    }

    public function testRunWrongIdentityObject(): void
    {
        $this->expectException(LogicException::class);

        $identity = $this->createMock(IdentityInterface::class);
        ;

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getIdentity')->willReturn(
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

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $workspace
            )
        );

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent->run()
        );
    }

    public function testCloningIntoPathBadPath(): void
    {
        $this->expectException(TypeError::class);
        $this->buildAgent()->cloningIntoPath(new DateTime());
    }

    public function testCloningIntoPathWithGenerator(): void
    {
        $this->expectException(RuntimeException::class);

        $agent = $this->buildAgent();

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath($path = 'foo', 'bar')
        );
    }

    public function testCloningIntoPathWithHttp(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getIdentity')->willReturn(null);
        $repository->method('getPullUrl')->willReturn(
            'http://foo.bar'
        );

        $agent = $this->buildAgent();

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        $this->expectException(InvalidArgumentException::class);
        $agent->cloningIntoPath('foo', '/bar');
    }

    public function testCloningIntoPathWithHttps(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getIdentity')->willReturn(null);
        $repository->method('getPullUrl')->willReturn(
            'https://foo.bar'
        );

        $agent = $this->buildAgent();

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath('foo', '/bar')
        );
    }

    public function testCloningIntoPathWithSSH(): void
    {
        $identity = $this->createMock(SshIdentity::class);
        $identity->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getIdentity')->willReturn(
            $identity
        );
        $repository->method('getPullUrl')->willReturn(
            'git@foo.bar'
        );

        $agent = $this->buildAgent();

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath('foo', '/bar')
        );
    }

    public function testCloningIntoPathErrorInExecution(): void
    {
        $identity = $this->createMock(SshIdentity::class);
        $identity->method('getPrivateKey')->willReturn($pk = 'fooBar');

        $repository = $this->createMock(GitRepository::class);
        $repository->method('getIdentity')->willReturn(
            $identity
        );
        $repository->method('getPullUrl')->willReturn(
            $url = 'git@foo:bar'
        );

        $this->getProcessFactoryMock(false);

        $agent = $this->buildAgent();

        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent = $agent->configure(
                $repository,
                $this->createMock(JobWorkspaceInterface::class)
            )
        );

        $this->expectException(RuntimeException::class);
        $this->assertInstanceOf(
            CloningAgentInterface::class,
            $agent->cloningIntoPath('foo', '/bar')
        );
    }

    public function testClone(): void
    {
        $agent = $this->buildAgent();
        $agent2 = clone $agent;

        $this->assertNotSame($agent, $agent2);
    }
}
