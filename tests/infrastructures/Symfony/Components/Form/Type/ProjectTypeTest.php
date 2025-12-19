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

namespace Teknoo\Tests\East\Paas\Infrastructures\Symfony\Form\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Paas\Infrastructures\Symfony\Form\Type\ProjectType;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\ImageRegistry;
use Teknoo\East\Paas\Object\GitRepository;
use Teknoo\East\Paas\Object\Project;
use Teknoo\East\Paas\Object\Cluster;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ProjectType::class)]
class ProjectTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm(): ProjectType
    {
        return new ProjectType();
    }

    private function getObject(): Project
    {
        return (new Project(new Account()));
    }

    private function getFormArray(): array
    {
        return [
            'name' => 'fooBar',
            'prefix' => 'fooBar',
            'sourceRepository' => new GitRepository(),
            'imagesRegistry' => new ImageRegistry(),
            'clusters' => [new Cluster()],
        ];
    }

    public function testConfigureOptions(): void
    {
        $this->buildForm()->configureOptions(
            $this->createStub(OptionsResolver::class)
        );
        $this->assertTrue(true);
    }
}
