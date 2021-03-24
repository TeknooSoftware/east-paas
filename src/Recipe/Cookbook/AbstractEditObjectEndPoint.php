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

namespace Teknoo\East\Paas\Recipe\Cookbook;

use Teknoo\East\Paas\Recipe\Traits\AdditionalStepsTrait;
use Teknoo\East\Website\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Website\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Website\Recipe\Cookbook\EditContentEndPoint;
use Teknoo\East\Website\Recipe\Step\LoadObject;
use Teknoo\East\Website\Recipe\Step\RenderError;
use Teknoo\East\Website\Recipe\Step\SaveObject;
use Teknoo\East\Website\Recipe\Step\SlugPreparation;
use Teknoo\Recipe\RecipeInterface;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
abstract class AbstractEditObjectEndPoint extends EditContentEndPoint
{
    use AdditionalStepsTrait;

    /**
     * @param iterable<callable> $additionalSteps
     */
    public function __construct(
        RecipeInterface $recipe,
        LoadObject $loadObject,
        FormHandlingInterface $formHandling,
        FormProcessingInterface $formProcessing,
        SlugPreparation $slugPreparation,
        SaveObject $saveObject,
        RenderFormInterface $renderForm,
        RenderError $renderError,
        ?ObjectAccessControlInterface $objectAccessControl = null,
        iterable $additionalSteps,
    ) {
        parent::__construct(
            $recipe,
            $loadObject,
            $formHandling,
            $formProcessing,
            $slugPreparation,
            $saveObject,
            $renderForm,
            $renderError,
            $objectAccessControl
        );

        $this->additionalSteps = $additionalSteps;
    }
}
