<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\East\Paas\Recipe\Step\Worker;

use Teknoo\East\Foundation\Http\Message\MessageFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface as ImageBuilder;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Recipe\Traits\ErrorTrait;
use Teknoo\East\Paas\Recipe\Traits\PsrFactoryTrait;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ConfigureImagesBuilder
{
    use ErrorTrait;
    use PsrFactoryTrait;

    private ImageBuilder $builder;

    public function __construct(
        ImageBuilder $builder,
        MessageFactoryInterface $messageFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->builder = $builder;
        $this->setMessageFactory($messageFactory);
        $this->setStreamFactory($streamFactory);
    }

    public function __invoke(
        JobUnitInterface $jobUnit,
        ClientInterface $client,
        ManagerInterface $manager
    ): self {
        $jobUnit->configureImageBuilder(
            $this->builder,
            new Promise(
                static function (ImageBuilder $builder) use ($manager) {
                    $manager->updateWorkPlan([ImageBuilder::class => $builder]);
                },
                static::buildFailurePromise(
                    $client,
                    $manager,
                    'teknoo.east.paas.error.recipe.images.configuration_error',
                    500,
                    $this->messageFactory,
                    $this->streamFactory
                )
            )
        );

        return $this;
    }
}
