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
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ProjectType::class)]
class ProjectTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm()
    {
        return new ProjectType();
    }

    private function getObject()
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

    public function testConfigureOptions()
    {
        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->configureOptions(
                $this->createMock(OptionsResolver::class)
            )
        );
    }
}
