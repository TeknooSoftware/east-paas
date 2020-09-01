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

namespace Teknoo\East\Paas\Object;

use Teknoo\East\Website\Object\DeletableInterface;
use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\East\Website\Object\User as BaseUser;
use Teknoo\East\Paas\Contracts\Object\FormMappingInterface;
use Teknoo\East\Paas\Object\Account\Active;
use Teknoo\East\Paas\Object\Account\Inactive;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\Assertion\Property\IsInstanceOf;
use Teknoo\States\Automated\Assertion\Property\IsNotInstanceOf;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Account implements
    ObjectInterface,
    TimestampableInterface,
    ProxyInterface,
    AutomatedInterface,
    DeletableInterface,
    FormMappingInterface
{
    use ObjectTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    protected ?string $name = null;

    protected ?BillingInformation $billingInformation = null;

    protected ?PaymentInformation $paymentInformation = null;

    /**
     * @var Project[]
     */
    protected iterable $projects = [];

    protected ?BaseUser $owner = null;

    public function __construct()
    {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
            Active::class,
            Inactive::class,
        ];
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property(Active::class))
                ->with('billingInformation', new IsInstanceOf(BillingInformation::class))
                ->with('paymentInformation', new IsInstanceOf(PaymentInformation::class)),
            (new Property(Inactive::class))
                ->with('billingInformation', new IsNotInstanceOf(BillingInformation::class)),
            (new Property(Inactive::class))
                ->with('paymentInformation', new IsNotInstanceOf(PaymentInformation::class)),
        ];
    }

    private function getName(): string
    {
        return (string) $this->name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function setName(string $name): Account
    {
        $this->name = $name;
        return $this;
    }

    private function getBillingInformation(): ?BillingInformation
    {
        return $this->billingInformation;
    }

    public function setBillingInformation(BillingInformation $billingInformation): Account
    {
        $this->billingInformation = $billingInformation;

        $this->updateStates();

        return $this;
    }

    private function getPaymentInformation(): ?PaymentInformation
    {
        return $this->paymentInformation;
    }

    public function setPaymentInformation(PaymentInformation $paymentInformation): Account
    {
        $this->paymentInformation = $paymentInformation;

        $this->updateStates();

        return $this;
    }

    /**
     * @param iterable<Project> $projects
     */
    public function setProjects(iterable $projects): Account
    {
        $this->projects = $projects;

        return $this;
    }

    private function getOwner(): ?BaseUser
    {
        return $this->owner;
    }

    public function setOwner(BaseUser $owner): Account
    {
        $this->owner = $owner;

        return $this;
    }

    public function injectDataInto($forms): FormMappingInterface
    {
        if (isset($forms['name'])) {
            $forms['name']->setData($this->getName());
        }

        if (isset($forms['owner'])) {
            $forms['owner']->setData($this->getOwner());
        }

        if (isset($forms['billingInformation'])) {
            $forms['billingInformation']->setData($this->getBillingInformation());
        }

        if (isset($forms['paymentInformation'])) {
            $forms['paymentInformation']->setData($this->getPaymentInformation());
        }

        return $this;
    }
}