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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Cookbook;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Contracts\Recipe\Step\History\DispatchHistoryInterface;
use Teknoo\East\Paas\Contracts\Recipe\Step\Misc\DispatchResultInterface;
use Teknoo\East\Paas\Recipe\Cookbook\RunJob;
use Teknoo\East\Paas\Recipe\Step\History\DisplayHistory;
use Teknoo\East\Paas\Recipe\Step\Job\DeserializeJob;
use Teknoo\East\Paas\Recipe\Step\Job\ReceiveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildImages;
use Teknoo\East\Paas\Recipe\Step\Worker\BuildVolumes;
use Teknoo\East\Paas\Recipe\Step\Worker\CloneRepository;
use Teknoo\East\Paas\Recipe\Step\Worker\CompileDeployment;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureCloningAgent;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureClusterClient;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureConductor;
use Teknoo\East\Paas\Recipe\Step\Worker\ConfigureImagesBuilder;
use Teknoo\East\Paas\Recipe\Step\Worker\Deploying;
use Teknoo\East\Paas\Recipe\Step\Worker\Exposing;
use Teknoo\East\Paas\Recipe\Step\Worker\HookBuildContainer;
use Teknoo\East\Paas\Recipe\Step\Worker\PrepareWorkspace;
use Teknoo\East\Paas\Recipe\Step\Worker\ReadDeploymentConfiguration;
use Teknoo\Recipe\CookbookInterface;
use Teknoo\Recipe\RecipeInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Cookbook\RunJob
 * @covers \Teknoo\East\Paas\Recipe\Cookbook\CookbookTrait
 */
class RunJobTest extends TestCase
{
    use CookbookTestTrait;

    public function buildCookbook(): CookbookInterface
    {
        return new RunJob(
            $this->createMock(RecipeInterface::class),
            $this->createMock(DispatchHistoryInterface::class),
            $this->createMock(ReceiveJob::class),
            $this->createMock(DeserializeJob::class),
            $this->createMock(PrepareWorkspace::class),
            $this->createMock(ConfigureCloningAgent::class),
            $this->createMock(CloneRepository::class),
            $this->createMock(ConfigureConductor::class),
            $this->createMock(ReadDeploymentConfiguration::class),
            $this->createMock(CompileDeployment::class),
            $this->createMock(HookBuildContainer::class),
            $this->createMock(ConfigureImagesBuilder::class),
            $this->createMock(BuildImages::class),
            $this->createMock(BuildVolumes::class),
            $this->createMock(ConfigureClusterClient::class),
            $this->createMock(Deploying::class),
            $this->createMock(Exposing::class),
            $this->createMock(DispatchResultInterface::class),
            $this->createMock(DisplayHistory::class),
            $this->createMock(DisplayError::class)
        );
    }
}
