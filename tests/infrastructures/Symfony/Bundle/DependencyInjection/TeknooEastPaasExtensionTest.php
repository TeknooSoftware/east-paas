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

namespace Teknoo\Tests\East\Paas\Infrastructures\EastPaasBundle\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Teknoo\East\Paas\Infrastructures\EastPaasBundle\DependencyInjection\TeknooEastPaasExtension;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TeknooEastPaasExtension::class)]
class TeknooEastPaasExtensionTest extends TestCase
{
    private (ContainerBuilder&MockObject)|null $container = null;

    private function getContainerBuilderMock(): ContainerBuilder&MockObject
    {
        if (!$this->container instanceof ContainerBuilder) {
            $this->container = $this->createMock(ContainerBuilder::class);
        }

        return $this->container;
    }

    private function buildExtension(): TeknooEastPaasExtension
    {
        return new TeknooEastPaasExtension();
    }

    private function getExtensionClass(): string
    {
        return TeknooEastPaasExtension::class;
    }

    public function testLoad(): void
    {
        $this->assertInstanceOf(
            $this->getExtensionClass(),
            $this->buildExtension()->load([], $this->getContainerBuilderMock())
        );
    }

    public function testLoadErrorContainer(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildExtension()->load([], new \stdClass());
    }

    public function testLoadErrorConfig(): void
    {
        $this->expectException(\TypeError::class);
        $this->buildExtension()->load(new \stdClass(), $this->getContainerBuilderMock());
    }
}
