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

namespace Teknoo\Tests\East\Paas\Recipe\Cookbook;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Recipe\Cookbook\AbstractEditObjectEndPoint;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\East\Paas\Recipe\Step;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Cookbook\BaseCookbookTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(AbstractEditObjectEndPoint::class)]
class EditPaaSObjectEndPointWithMappingsTest extends TestCase
{
    use BaseCookbookTestTrait;

    private ?RecipeInterface $recipe = null;

    private ?LoadObject $loadObject = null;

    private ?ObjectAccessControlInterface $objectAccessControl = null;

    private ?FormHandlingInterface $formHandling = null;

    private ?FormProcessingInterface $formProcessing = null;

    private ?SaveObject $saveObject = null;

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

    public function buildCookbook(): AbstractEditObjectEndPoint
    {
        return new class(
            $this->getRecipe(),
            $this->getLoadObject(),
            $this->getFormHandling(),
            $this->getFormProcessing(),
            $this->getSaveObject(),
            $this->getRenderForm(),
            $this->getRenderError(),
            $this->getObjectAccessControl(),
            [
                static function () {},
                new class {
                    public function __invoke() {}
                },
                [
                    new class {
                        public function foo() {}
                    },
                    'foo',
                ],
                new Step(
                    static function () {},
                    ['foo' => 'bar']
                ),
            ],
            'foo.template',
            ['foo' => 'bar']
        ) extends AbstractEditObjectEndPoint {

        };
    }
}
