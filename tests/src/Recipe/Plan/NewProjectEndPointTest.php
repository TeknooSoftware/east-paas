<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

namespace Teknoo\Tests\East\Paas\Recipe\Plan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Paas\Recipe\Plan\NewProjectEndPoint;
use Teknoo\East\Common\Contracts\Recipe\Step\FormHandlingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\FormProcessingInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\ObjectAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RedirectClientInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\RenderFormInterface;
use Teknoo\East\Common\Recipe\Step\CreateObject;
use Teknoo\East\Common\Recipe\Step\LoadObject;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Common\Recipe\Step\SaveObject;
use Teknoo\East\Paas\Recipe\Step;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\BasePlanTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(NewProjectEndPoint::class)]
class NewProjectEndPointTest extends TestCase
{
    use BasePlanTestTrait;

    private (RecipeInterface&Stub)|(RecipeInterface&MockObject)|null $recipe = null;

    private (LoadObject&Stub)|(LoadObject&MockObject)|null $loadObject = null;

    private (ObjectAccessControlInterface&Stub)|(ObjectAccessControlInterface&MockObject)|null $objectAccessControl = null;

    private (FormHandlingInterface&Stub)|(FormHandlingInterface&MockObject)|null $formHandling = null;

    private (CreateObject&Stub)|(CreateObject&MockObject)|null $createObject = null;

    private (FormProcessingInterface&Stub)|(FormProcessingInterface&MockObject)|null $formProcessing = null;

    private (SaveObject&Stub)|(SaveObject&MockObject)|null $saveObject = null;

    private (RedirectClientInterface&Stub)|(RedirectClientInterface&MockObject)|null $redirectClient = null;

    private (RenderFormInterface&Stub)|(RenderFormInterface&MockObject)|null $renderForm = null;

    private (RenderError&Stub)|(RenderError&MockObject)|null $renderError = null;

    public function getRecipe(bool $stub = false): (RecipeInterface&Stub)|(RecipeInterface&MockObject)
    {
        if (!$this->recipe instanceof RecipeInterface) {
            if ($stub) {
                $this->recipe = $this->createStub(RecipeInterface::class);
            } else {
                $this->recipe = $this->createMock(RecipeInterface::class);
            }
        }

        return $this->recipe;
    }

    public function getLoadObject(bool $stub = false): (LoadObject&Stub)|(LoadObject&MockObject)
    {
        if (!$this->loadObject instanceof LoadObject) {
            if ($stub) {
                $this->loadObject = $this->createStub(LoadObject::class);
            } else {
                $this->loadObject = $this->createMock(LoadObject::class);
            }
        }

        return $this->loadObject;
    }

    public function getObjectAccessControl(bool $stub = false): (ObjectAccessControlInterface&Stub)|(ObjectAccessControlInterface&MockObject)
    {
        if (!$this->objectAccessControl instanceof ObjectAccessControlInterface) {
            if ($stub) {
                $this->objectAccessControl = $this->createStub(ObjectAccessControlInterface::class);
            } else {
                $this->objectAccessControl = $this->createMock(ObjectAccessControlInterface::class);
            }
        }

        return $this->objectAccessControl;
    }

    public function getFormHandling(bool $stub = false): (FormHandlingInterface&Stub)|(FormHandlingInterface&MockObject)
    {
        if (!$this->formHandling instanceof FormHandlingInterface) {
            if ($stub) {
                $this->formHandling = $this->createStub(FormHandlingInterface::class);
            } else {
                $this->formHandling = $this->createMock(FormHandlingInterface::class);
            }
        }

        return $this->formHandling;
    }

    public function getCreateObject(bool $stub = false): (CreateObject&Stub)|(CreateObject&MockObject)
    {
        if (!$this->createObject instanceof CreateObject) {
            if ($stub) {
                $this->createObject = $this->createStub(CreateObject::class);
            } else {
                $this->createObject = $this->createMock(CreateObject::class);
            }
        }

        return $this->createObject;
    }

    public function getFormProcessing(bool $stub = false): (FormProcessingInterface&Stub)|(FormProcessingInterface&MockObject)
    {
        if (!$this->formProcessing instanceof FormProcessingInterface) {
            if ($stub) {
                $this->formProcessing = $this->createStub(FormProcessingInterface::class);
            } else {
                $this->formProcessing = $this->createMock(FormProcessingInterface::class);
            }
        }

        return $this->formProcessing;
    }

    public function getSaveObject(bool $stub = false): (SaveObject&Stub)|(SaveObject&MockObject)
    {
        if (!$this->saveObject instanceof SaveObject) {
            if ($stub) {
                $this->saveObject = $this->createStub(SaveObject::class);
            } else {
                $this->saveObject = $this->createMock(SaveObject::class);
            }
        }

        return $this->saveObject;
    }

    public function getRedirectClient(bool $stub = false): (RedirectClientInterface&Stub)|(RedirectClientInterface&MockObject)
    {
        if (!$this->redirectClient instanceof RedirectClientInterface) {
            if ($stub) {
                $this->redirectClient = $this->createStub(RedirectClientInterface::class);
            } else {
                $this->redirectClient = $this->createMock(RedirectClientInterface::class);
            }
        }

        return $this->redirectClient;
    }

    public function getRenderForm(bool $stub = false): (RenderFormInterface&Stub)|(RenderFormInterface&MockObject)
    {
        if (!$this->renderForm instanceof RenderFormInterface) {
            if ($stub) {
                $this->renderForm = $this->createStub(RenderFormInterface::class);
            } else {
                $this->renderForm = $this->createMock(RenderFormInterface::class);
            }
        }

        return $this->renderForm;
    }

    public function getRenderError(bool $stub = false): (RenderError&Stub)|(RenderError&MockObject)
    {
        if (!$this->renderError instanceof RenderError) {
            if ($stub) {
                $this->renderError = $this->createStub(RenderError::class);
            } else {
                $this->renderError = $this->createMock(RenderError::class);
            }
        }

        return $this->renderError;
    }

    public function buildPlan(): NewProjectEndPoint
    {
        return new NewProjectEndPoint(
            $this->getRecipe(true),
            $this->getLoadObject(true),
            $this->getObjectAccessControl(true),
            $this->getCreateObject(true),
            $this->getFormHandling(true),
            $this->getFormProcessing(true),
            $this->getSaveObject(true),
            $this->getRedirectClient(true),
            $this->getRenderForm(true),
            $this->getRenderError(true),
        );
    }
}
