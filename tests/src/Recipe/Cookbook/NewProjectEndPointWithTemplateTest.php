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

namespace Teknoo\Tests\East\Paas\Recipe\Cookbook;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Recipe\Cookbook\NewProjectEndPoint;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Step\CreateObject;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Cookbook\BaseCookbookTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Recipe\Cookbook\NewProjectEndPoint
 * @covers \Teknoo\East\Paas\Recipe\Traits\AdditionalStepsTrait
 */
class NewProjectEndPointWithTemplateTest extends TestCase
{
    use BaseCookbookTestTrait;

    private ?RecipeInterface $recipe = null;

    private ?LoadObject $loadObject = null;

    private ?ObjectAccessControlInterface $objectAccessControl = null;

    private ?FormHandlingInterface $formHandling = null;

    private ?CreateObject $createObject = null;

    private ?FormProcessingInterface $formProcessing = null;

    private ?SaveObject $saveObject = null;

    private ?RedirectClientInterface $redirectClient = null;

    private ?RenderFormInterface $renderForm = null;

    private ?RenderError $renderError = null;

    /**
     * @return RecipeInterface|MockObject
     */
    public function getRecipe(): RecipeInterface
    {
        if (null === $this->recipe) {
            $this->recipe = $this->createMock(RecipeInterface::class);
        }

        return $this->recipe;
    }

    /**
     * @return LoadObject|MockObject
     */
    public function getLoadObject(): LoadObject
    {
        if (null === $this->loadObject) {
            $this->loadObject = $this->createMock(LoadObject::class);
        }

        return $this->loadObject;
    }

    /**
     * @return ObjectAccessControlInterface|MockObject
     */
    public function getObjectAccessControl(): ObjectAccessControlInterface
    {
        if (null === $this->objectAccessControl) {
            $this->objectAccessControl = $this->createMock(ObjectAccessControlInterface::class);
        }

        return $this->objectAccessControl;
    }

    /**
     * @return FormHandlingInterface|MockObject
     */
    public function getFormHandling(): FormHandlingInterface
    {
        if (null === $this->formHandling) {
            $this->formHandling = $this->createMock(FormHandlingInterface::class);
        }

        return $this->formHandling;
    }

    /**
     * @return CreateObject|MockObject
     */
    public function getCreateObject(): CreateObject
    {
        if (null === $this->createObject) {
            $this->createObject = $this->createMock(CreateObject::class);
        }

        return $this->createObject;
    }

    /**
     * @return FormProcessingInterface|MockObject
     */
    public function getFormProcessing(): FormProcessingInterface
    {
        if (null === $this->formProcessing) {
            $this->formProcessing = $this->createMock(FormProcessingInterface::class);
        }

        return $this->formProcessing;
    }

    /**
     * @return SaveObject|MockObject
     */
    public function getSaveObject(): SaveObject
    {
        if (null === $this->saveObject) {
            $this->saveObject = $this->createMock(SaveObject::class);
        }

        return $this->saveObject;
    }

    /**
     * @return RedirectClientInterface|MockObject
     */
    public function getRedirectClient(): RedirectClientInterface
    {
        if (null === $this->redirectClient) {
            $this->redirectClient = $this->createMock(RedirectClientInterface::class);
        }

        return $this->redirectClient;
    }

    /**
     * @return RenderFormInterface|MockObject
     */
    public function getRenderForm(): RenderFormInterface
    {
        if (null === $this->renderForm) {
            $this->renderForm = $this->createMock(RenderFormInterface::class);
        }

        return $this->renderForm;
    }

    /**
     * @return RenderError|MockObject
     */
    public function getRenderError(): RenderError
    {
        if (null === $this->renderError) {
            $this->renderError = $this->createMock(RenderError::class);
        }

        return $this->renderError;
    }

    public function buildCookbook(): NewProjectEndPoint
    {
        return new NewProjectEndPoint(
            $this->getRecipe(),
            $this->getLoadObject(),
            $this->getObjectAccessControl(),
            $this->getCreateObject(),
            $this->getFormHandling(),
            $this->getFormProcessing(),
            $this->getSaveObject(),
            $this->getRedirectClient(),
            $this->getRenderForm(),
            $this->getRenderError(),
            [
                static function () {},
                static function () {},
            ],
            'foo.template',
            ['foo' => 'bar'],
        );
    }
}