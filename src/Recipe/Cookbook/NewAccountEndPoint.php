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

use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
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
        SaveObject $saveObject,
        RedirectClientInterface $redirectClient,
        RenderFormInterface $renderForm,
        RenderError $renderError,
        iterable $additionalSteps,
        ?ObjectAccessControlInterface $objectAccessControl = null,
        ?string $defaultErrorTemplate = null,
        array $createObjectWiths = [],
    ) {
        parent::__construct(
            $recipe,
            $createObject,
            $formHandling,
            $formProcessing,
            null,
            $saveObject,
            $redirectClient,
            $renderForm,
            $renderError,
            $objectAccessControl,
            $defaultErrorTemplate,
            $createObjectWiths,
        );

        $this->additionalSteps = $additionalSteps;
    }
}
