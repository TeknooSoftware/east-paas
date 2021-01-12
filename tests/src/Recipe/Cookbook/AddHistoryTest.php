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
use Teknoo\East\Paas\Recipe\Cookbook\AddHistory;
use Teknoo\East\Paas\Recipe\Step\History\AddHistory as StepAddHistory;
use Teknoo\East\Paas\Recipe\Step\History\DeserializeHistory;
use Teknoo\East\Paas\Recipe\Step\History\DisplayHistory;
use Teknoo\East\Paas\Recipe\Step\History\ReceiveHistory;
use Teknoo\East\Paas\Recipe\Step\History\SerializeHistory;
use Teknoo\East\Paas\Recipe\Step\Job\GetJob;
use Teknoo\East\Paas\Recipe\Step\Job\SaveJob;
use Teknoo\East\Paas\Recipe\Step\Misc\DisplayError;
use Teknoo\East\Paas\Recipe\Step\Project\GetProject;
use Teknoo\Recipe\CookbookInterface;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Cookbook\BaseCookbookTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Cookbook\AddHistory
 */
class AddHistoryTest extends TestCase
{
    use BaseCookbookTestTrait;

    public function buildCookbook(): CookbookInterface
    {
        return new AddHistory(
            $this->createMock(RecipeInterface::class),
            $this->createMock(ReceiveHistory::class),
            $this->createMock(DeserializeHistory::class),
            $this->createMock(GetProject::class),
            $this->createMock(GetJob::class),
            $this->createMock(StepAddHistory::class),
            $this->createMock(SaveJob::class),
            $this->createMock(SerializeHistory::class),
            $this->createMock(DisplayHistory::class),
            $this->createMock(DisplayError::class)
        );
    }
}
