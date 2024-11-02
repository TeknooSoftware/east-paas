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

namespace Teknoo\East\Paas\Recipe\Plan;

use Stringable;
use Teknoo\East\Paas\Contracts\Recipe\Plan\NewProjectEndPointInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Plan\CreateObjectEndPoint;
use Teknoo\East\Common\Recipe\Step\CreateObject;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\Recipe\Ingredient\Ingredient;
use Teknoo\Recipe\RecipeInterface;

/**
 * Plan to create a new account on the platform via an HTTP Endpoint.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class NewProjectEndPoint extends CreateObjectEndPoint implements NewProjectEndPointInterface
{
    public function __construct(
        RecipeInterface $recipe,
        private readonly LoadObject $loadObject,
        ?ObjectAccessControlInterface $objectAccessControl,
        CreateObject $createObject,
        FormHandlingInterface $formHandling,
        FormProcessingInterface $formProcessing,
        SaveObject $saveObject,
        RedirectClientInterface $redirectClient,
        RenderFormInterface $renderForm,
        RenderError $renderError,
        string|Stringable|null $defaultErrorTemplate = null,
        array $createObjectWiths = [],
    ) {
        parent::__construct(
            recipe: $recipe,
            createObject: $createObject,
            formHandling: $formHandling,
            formProcessing: $formProcessing,
            slugPreparation: null,
            saveObject: $saveObject,
            redirectClient: $redirectClient,
            renderForm: $renderForm,
            renderError: $renderError,
            objectAccessControl: $objectAccessControl,
            defaultErrorTemplate: $defaultErrorTemplate,
            createObjectWiths: $createObjectWiths,
        );
    }

    protected function populateRecipe(RecipeInterface $recipe): RecipeInterface
    {
        $recipe = $recipe->require(new Ingredient('string', 'accountId'));

        $recipe = $recipe->cook(
            $this->loadObject,
            LoadObject::class . ':Account',
            [
                'loader' => 'accountLoader',
                'id' => 'accountId',
                'workPlanKey' => 'accountKey'
            ],
            05
        );

        return parent::populateRecipe($recipe);
    }
}
