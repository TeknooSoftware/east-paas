<?php

declare(strict_types=1);

/*
 * East Paas.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\EndPoint;

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Foundation\EndPoint\EndPointInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\FoundationBundle\EndPoint\EastEndPointTrait;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\WebsiteBundle\AdminEndPoint\AdminEndPointTrait;
use Teknoo\East\WebsiteBundle\AdminEndPoint\AdminFormTrait;
use Teknoo\East\Paas\Loader\AccountLoader;
use Teknoo\East\Paas\Object\Account;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class NewProjectEndPoint implements EndPointInterface
{
    use EastEndPointTrait;
    use AdminEndPointTrait;
    use AdminFormTrait;

    private string $objectClass;

    private AccountLoader $accountLoader;

    /**
     * @var array<string, mixed>
     */
    private array $formOptions = [];

    /**
     * @param array<string, mixed> $formOptions
     */
    public function setFormOptions(array $formOptions): self
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    public function setObjectClass(string $objectClass): self
    {
        if (!\class_exists($objectClass)) {
            throw new \LogicException("Error the object class $objectClass is not available");
        }

        $this->objectClass = $objectClass;

        return $this;
    }

    public function setAccountLoader(AccountLoader $accountLoader): self
    {
        $this->accountLoader = $accountLoader;

        return $this;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ClientInterface $client,
        string $editRoute,
        string $accountId,
        bool $isTranslatable = false,
        string $viewPath = null
    ): self {
        if (null === $viewPath) {
            $viewPath = $this->viewPath;
        }

        $class = $this->objectClass;

        $this->accountLoader->load(
            $accountId,
            new Promise(
                function (Account $account) use ($class, $request, $client, $editRoute, $isTranslatable, $viewPath) {
                    $object = new $class($account);
                    $form = $this->createForm($object, $this->formOptions);
                    $form->handleRequest($request->getAttribute('request'));

                    if ($form->isSubmitted() && $form->isValid()) {
                        $this->writer->save($object, new Promise(
                            function (ObjectInterface $object) use ($client, $editRoute) {
                                $this->redirectToRoute($client, $editRoute, ['id' => $object->getId()]);
                            },
                            function ($error) use ($client) {
                                $client->errorInRequest($error);
                            }
                        ));

                        return;
                    }

                    $this->render(
                        $client,
                        $viewPath,
                        [
                            'objectInstance' => $object,
                            'formView' => $form->createView(),
                            'request' => $request,
                            'isTranslatable' => $isTranslatable
                        ]
                    );
                },
                function (\Throwable $e) use ($client) {
                    $client->errorInRequest($e);
                }
            )
        );

        return $this;
    }
}
