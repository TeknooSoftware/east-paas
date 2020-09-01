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

use Teknoo\East\Website\Object\ObjectInterface;
use Teknoo\East\Website\Object\ObjectTrait;
use Teknoo\East\Website\Object\TimestampableInterface;
use Teknoo\East\Paas\Contracts\Object\FormMappingInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class BillingInformation implements ObjectInterface, TimestampableInterface, FormMappingInterface
{
    use ObjectTrait;

    private ?string $name = null;

    private ?string $service = null;

    private ?string $address = null;

    private ?string $zip = null;

    private ?string $city = null;

    private ?string $country = null;

    private ?string $vat = null;

    private function getName(): string
    {
        return (string) $this->name;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function setName(?string $name): BillingInformation
    {
        $this->name = $name;
        return $this;
    }

    private function getService(): string
    {
        return (string) $this->service;
    }

    public function setService(?string $service): BillingInformation
    {
        $this->service = $service;
        return $this;
    }

    private function getAddress(): string
    {
        return (string) $this->address;
    }

    public function setAddress(?string $address): BillingInformation
    {
        $this->address = $address;
        return $this;
    }

    private function getZip(): string
    {
        return (string) $this->zip;
    }

    public function setZip(?string $zip): BillingInformation
    {
        $this->zip = $zip;
        return $this;
    }

    private function getCity(): string
    {
        return (string) $this->city;
    }

    public function setCity(?string $city): BillingInformation
    {
        $this->city = $city;
        return $this;
    }

    private function getCountry(): string
    {
        return (string) $this->country;
    }

    public function setCountry(?string $country): BillingInformation
    {
        $this->country = $country;
        return $this;
    }

    private function getVat(): string
    {
        return (string) $this->vat;
    }

    public function setVat(?string $vat): BillingInformation
    {
        $this->vat = $vat;
        return $this;
    }

    public function injectDataInto($forms): FormMappingInterface
    {
        if (isset($forms['name'])) {
            $forms['name']->setData($this->getName());
        }

        if (isset($forms['service'])) {
            $forms['service']->setData($this->getService());
        }

        if (isset($forms['address'])) {
            $forms['address']->setData($this->getAddress());
        }

        if (isset($forms['zip'])) {
            $forms['zip']->setData($this->getZip());
        }

        if (isset($forms['city'])) {
            $forms['city']->setData($this->getCity());
        }

        if (isset($forms['country'])) {
            $forms['country']->setData($this->getCountry());
        }

        if (isset($forms['vat'])) {
            $forms['vat']->setData($this->getVat());
        }

        return $this;
    }
}
