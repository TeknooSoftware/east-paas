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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Recipe\Cookbook;

use Teknoo\East\Paas\Contracts\Recipe\Cookbook\NewAccountEndPointInterface;
use Teknoo\East\Paas\Recipe\Traits\AdditionalStepsTrait;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Cookbook\CreateObjectEndPoint;
use Teknoo\East\Common\Recipe\Step\CreateObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\East\Common\Recipe\Step\SlugPreparation;
use Teknoo\Recipe\RecipeInterface;

/**
 * Cookbook to create a new account on the platform via an HTTP Endpoint.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class NewAccountEndPoint extends CreateObjectEndPoint implements NewAccountEndPointInterface
{
    use AdditionalStepsTrait;

    /**
     * @param iterable<int, callable> $additionalSteps
     */
    public function __construct(
        RecipeInterface $recipe,
        CreateObject $createObject,
        FormHandlingInterface $formHandling,
        FormProcessingInterface $formProcessing,
        SlugPreparation $slugPreparation,
        SaveObject $saveObject,
        RedirectClientInterface $redirectClient,
        RenderFormInterface $renderForm,
        RenderError $renderError,
        iterable $additionalSteps,
    ) {
        parent::__construct(
            $recipe,
            $createObject,
            $formHandling,
            $formProcessing,
            $slugPreparation,
            $saveObject,
            $redirectClient,
            $renderForm,
            $renderError
        );

        $this->additionalSteps = $additionalSteps;
    }
}
