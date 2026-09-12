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

namespace Teknoo\Tests\East\Paas\Compilation;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as QuotaFactory;
use Teknoo\East\Paas\Compilation\Conductor;
use Teknoo\East\Paas\Compilation\Exception\AlreadyDefinedException;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Ingress;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Expose\Service;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Pod;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Hook\HooksCollectionInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\Recipe\Promise\PromiseInterface;
use Throwable;

use function dirname;
use function file_get_contents;

/**
 * Check that the shortcuts introduced in the PaaS schema v1.2 (`services` in containers and `ingress` in services)
 * produce exactly the same compiled deployment than the explicit definitions in the schema v1.1.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversNothing]
class SchemaEquivalenceTest extends TestCase
{
    private function buildContainer(): Container
    {
        $containerDefinition = new ContainerBuilder();
        $containerDefinition->addDefinitions(dirname(__DIR__, 3) . '/src/di.php');
        $containerDefinition->addDefinitions(
            dirname(__DIR__, 3) . '/infrastructures/Symfony/Components/di.php'
        );
        $containerDefinition->addDefinitions([
            'teknoo.east.paas.worker.add_history_pattern' => 'foo',
            'teknoo.east.paas.root_dir' => '/foo',
            'teknoo.east.paas.symfony.property_accessor' => PropertyAccess::createPropertyAccessor(),
            HooksCollectionInterface::class => fn () => $this->createStub(HooksCollectionInterface::class),
        ]);

        return $containerDefinition->build();
    }

    private function buildJobUnit(): JobUnitInterface
    {
        $jobUnit = $this->createStub(JobUnitInterface::class);
        $jobUnit->method('getEnvironmentTag')->willReturn('prod');
        $jobUnit->method('getProjectNormalizedName')->willReturn('test');
        $jobUnit->method('updateVariablesIn')->willReturnCallback(
            function (array $values, PromiseInterface $promise) use ($jobUnit): JobUnitInterface {
                $promise->success($values);

                return $jobUnit;
            }
        );
        $jobUnit->method('filteringConditions')->willReturnCallback(
            function (array $values, PromiseInterface $promise) use ($jobUnit): JobUnitInterface {
                $promise->success($values);

                return $jobUnit;
            }
        );
        $jobUnit->method('prepareQuotas')->willReturnCallback(
            function (QuotaFactory $factory, PromiseInterface $promise) use ($jobUnit): JobUnitInterface {
                $promise->success([]);

                return $jobUnit;
            }
        );

        return $jobUnit;
    }

    private function compileFixture(string $fixture): CompiledDeploymentInterface
    {
        $conductor = $this->buildContainer()->get(Conductor::class);
        $conductor = $conductor->configure(
            $this->buildJobUnit(),
            $this->createStub(JobWorkspaceInterface::class),
        );

        $configuration = (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/' . $fixture);

        $conductor->prepare(
            $configuration,
            new Promise(
                static fn (array $result): array => $result,
                static fn (Throwable $error): never => throw $error,
            ),
        );

        $compiled = null;
        $conductor->compileDeployment(
            new Promise(
                static function (CompiledDeploymentInterface $result) use (&$compiled): void {
                    $compiled = $result;
                },
                static fn (Throwable $error): never => throw $error,
            ),
        );

        $this->assertInstanceOf(CompiledDeploymentInterface::class, $compiled);

        return $compiled;
    }

    /**
     * @return array{pods: array<string, Pod>, services: array<string, Service>, ingresses: array<string, Ingress>}
     */
    private function extract(CompiledDeploymentInterface $compiledDeployment): array
    {
        $extracted = [
            'pods' => [],
            'services' => [],
            'ingresses' => [],
        ];

        $compiledDeployment->foreachPod(
            static function (Pod $pod) use (&$extracted): void {
                $extracted['pods'][$pod->getName()] = $pod;
            }
        );

        $compiledDeployment->foreachService(
            static function (Service $service) use (&$extracted): void {
                $extracted['services'][$service->getName()] = $service;
            }
        );

        $compiledDeployment->foreachIngress(
            static function (Ingress $ingress) use (&$extracted): void {
                $extracted['ingresses'][$ingress->getName()] = $ingress;
            }
        );

        return $extracted;
    }

    public function testShortcutsInV1dot2ProduceTheSameDeploymentThanExplicitDefinitionsInV1dot1(): void
    {
        $explicit = $this->compileFixture('expose_explicit_v1.1.paas.yaml');
        $shortcuts = $this->compileFixture('expose_shortcuts_v1.2.paas.yaml');

        $this->assertEquals(1.1, $explicit->getVersion());
        $this->assertEquals(1.2, $shortcuts->getVersion());

        $explicitExtracted = $this->extract($explicit);
        $shortcutsExtracted = $this->extract($shortcuts);

        $this->assertEquals(
            ['php-pods', 'demo'],
            array_keys($shortcutsExtracted['pods']),
        );
        $this->assertEqualsCanonicalizing(
            ['php-pods-php-run', 'php-pods-php-run-2', 'php-pods-sidecar', 'demo-nginx', 'legacy'],
            array_keys($shortcutsExtracted['services']),
        );
        $this->assertEqualsCanonicalizing(
            ['demo-nginx', 'legacy', 'demo-secure'],
            array_keys($shortcutsExtracted['ingresses']),
        );

        $listens = [];
        foreach ($shortcutsExtracted['pods']['php-pods'] as $container) {
            $listens[$container->getName()] = $container->getListen();
        }

        $this->assertEquals(
            [
                'php-run' => [8080, 9000, 8443],
                'sidecar' => [8181],
            ],
            $listens,
        );

        $this->assertEquals($explicitExtracted['pods'], $shortcutsExtracted['pods']);
        $this->assertEquals($explicitExtracted['services'], $shortcutsExtracted['services']);
        $this->assertEquals($explicitExtracted['ingresses'], $shortcutsExtracted['ingresses']);
    }

    private function compileFixtureWithAdditionalDefinition(string $section, string $yamlBlock): void
    {
        $configuration = (string) file_get_contents(
            dirname(__DIR__, 2) . '/fixtures/expose_shortcuts_v1.2.paas.yaml'
        );

        $sectionStart = "\n$section:\n";
        $this->assertStringContainsString($sectionStart, $configuration);
        $configuration = str_replace($sectionStart, $sectionStart . $yamlBlock . "\n", $configuration);

        $conductor = $this->buildContainer()->get(Conductor::class);
        $conductor = $conductor->configure(
            $this->buildJobUnit(),
            $this->createStub(JobWorkspaceInterface::class),
        );

        $conductor->prepare(
            $configuration,
            new Promise(
                static fn (array $result): array => $result,
                static fn (Throwable $error): never => throw $error,
            ),
        );

        $conductor->compileDeployment(
            new Promise(
                static fn (CompiledDeploymentInterface $result): CompiledDeploymentInterface => $result,
                static fn (Throwable $error): never => throw $error,
            ),
        );
    }

    public function testServiceDuplicatedByShortcutIsRejected(): void
    {
        $this->expectException(AlreadyDefinedException::class);
        $this->expectExceptionMessage('Service demo-nginx is already defined in the deployment');

        $this->compileFixtureWithAdditionalDefinition(
            'services',
            <<<'YAML'
  demo-nginx:
    pod: demo
    ports:
      - listen: 80
        target: 8080
YAML
        );
    }

    public function testIngressDuplicatedByShortcutIsRejected(): void
    {
        $this->expectException(AlreadyDefinedException::class);
        $this->expectExceptionMessage('Ingress demo-nginx is already defined in the deployment');

        $this->compileFixtureWithAdditionalDefinition(
            'ingresses',
            <<<'YAML'
  demo-nginx:
    host: other.teknoo.software
    tls:
      secret: demo-vault
    service:
      name: demo-nginx
      port: 8080
YAML
        );
    }

    public function testShortcutsAreRejectedInV1dot1(): void
    {
        $this->expectException(Throwable::class);

        $configuration = (string) file_get_contents(
            dirname(__DIR__, 2) . '/fixtures/expose_shortcuts_v1.2.paas.yaml'
        );

        $conductor = $this->buildContainer()->get(Conductor::class);
        $conductor = $conductor->configure(
            $this->buildJobUnit(),
            $this->createStub(JobWorkspaceInterface::class),
        );

        $conductor->prepare(
            str_replace('version: v1.2', 'version: v1.1', $configuration),
            new Promise(
                static fn (array $result): array => $result,
                static fn (Throwable $error): never => throw $error,
            ),
        );
    }
}
