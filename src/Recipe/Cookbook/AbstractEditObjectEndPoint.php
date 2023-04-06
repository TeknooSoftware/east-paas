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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe\Cookbook;

use Teknoo\East\Paas\Recipe\Traits\AdditionalStepsTrait;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Cookbook\EditObjectEndPoint;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\Recipe\RecipeInterface;

/**
 * Abstract cookbook to implement HTTP Endpoint
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractEditObjectEndPoint extends EditObjectEndPoint
{
    use AdditionalStepsTrait;

    /**
     * @param iterable<int, callable> $additionalSteps
     */
    public function __construct(
        RecipeInterface $recipe,
        LoadObject $loadObject,
        FormHandlingInterface $formHandling,
        FormProcessingInterface $formProcessing,
        SaveObject $saveObject,
        RenderFormInterface $renderForm,
        RenderError $renderError,
        ?ObjectAccessControlInterface $objectAccessControl = null,
        iterable $additionalSteps = [],
        ?string $defaultErrorTemplate = null,
        array $loadObjectWiths = [],
    ) {
        parent::__construct(
            $recipe,
            $loadObject,
            $formHandling,
            $formProcessing,
            null,
            $saveObject,
            $renderForm,
            $renderError,
            $objectAccessControl,
            $defaultErrorTemplate,
            $loadObjectWiths,
        );

        $this->additionalSteps = $additionalSteps;
    }
}
