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
 * @link        https://teknoo.software/east-collection/paas Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\East\Paas\Recipe\Plan;

use Stringable;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Plan\EditObjectEndPoint;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\Recipe\RecipeInterface;

/**
 * Abstract plan to implement HTTP Endpoint
 * Used directly in the DI to create `EditAccountEndPointInterface` and `EditProjectEndPointInterface`
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractEditObjectEndPoint extends EditObjectEndPoint
{
    public function __construct(
        RecipeInterface $recipe,
        LoadObject $loadObject,
        FormHandlingInterface $formHandling,
        FormProcessingInterface $formProcessing,
        SaveObject $saveObject,
        RenderFormInterface $renderForm,
        RenderError $renderError,
        ?ObjectAccessControlInterface $objectAccessControl = null,
        string|Stringable|null $defaultErrorTemplate = null,
        array $loadObjectWiths = [],
    ) {
        parent::__construct(
            recipe: $recipe,
            loadObject: $loadObject,
            formHandling: $formHandling,
            formProcessing: $formProcessing,
            slugPreparation: null,
            saveObject: $saveObject,
            renderForm: $renderForm,
            renderError: $renderError,
            objectAccessControl: $objectAccessControl,
            defaultErrorTemplate: $defaultErrorTemplate,
            loadObjectWiths: $loadObjectWiths,
        );
    }
}
