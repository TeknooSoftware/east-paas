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

namespace Teknoo\Tests\East\Paas\Infrastructures\EastPaasBundle;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\YamlFileLoader;

use function dirname;

/**
 * Check that the routes of the admin deleting objects check the CSRF token, with the step `CheckCsrfToken` of East
 * Common, enabled by the route's default `csrfTokenId`.
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversNothing]
class RoutingTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function deleteRoutesProvider(): array
    {
        return [
            'account' => ['routing_admin_account.yaml', '_teknoo_paas_admin_account_delete'],
            'project' => ['routing_admin_project.yaml', '_teknoo_paas_admin_project_delete'],
            'job' => ['routing_admin_job.yaml', '_teknoo_paas_admin_job_delete'],
        ];
    }

    #[DataProvider('deleteRoutesProvider')]
    public function testDeleteRouteChecksTheCsrfToken(string $file, string $routeName): void
    {
        $loader = new YamlFileLoader(
            new FileLocator(dirname(__DIR__, 4) . '/infrastructures/Symfony/Bundle/config')
        );

        $route = $loader->load($file)->get($routeName);

        $this->assertNotNull($route);
        $this->assertEquals('teknoo.east.common.endpoint.crud.delete', $route->getDefault('_controller'));
        $this->assertEquals('teknoo_east_paas_admin_delete', $route->getDefault('csrfTokenId'));
        $this->assertEquals(['GET', 'POST', 'DELETE'], $route->getMethods());
    }
}
