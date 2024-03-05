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

namespace Teknoo\East\Paas\Compilation\Compiler\Quota;

use Teknoo\East\Paas\Compilation\CompiledDeployment\AutomaticResource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Resource;
use Teknoo\East\Paas\Compilation\CompiledDeployment\ResourceSet;
use Teknoo\East\Paas\Compilation\Compiler\Exception\QuotaWrongConfigurationException;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceCapacityExceededException;
use Teknoo\East\Paas\Compilation\Compiler\Exception\QuotasNotCompliantException;
use Teknoo\East\Paas\Compilation\Compiler\Exception\ResourceWrongConfigurationException;
use Teknoo\East\Paas\Contracts\Compilation\Quota\AvailabilityInterface;

use function ceil;
use function sprintf;
use function str_ends_with;

/**
 * Abstract class to factories all quotas features defined in `AvailabilityInterface`
 * All quota categories share the same behavior, but each category manages its own capacity notation (K, Ki, etc..)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
abstract class AbstractAvailability implements AvailabilityInterface
{
    protected int $normalizedCapacity = 0;

    protected int $normalizedRequire = 0;

    public function __construct(
        protected readonly string $type,
        protected string $capacity,
        protected string $require,
        private readonly bool $isSoft,
    ) {
        if (!$this->isSoft && $this->isRelative($this->capacity)) {
            throw new QuotaWrongConfigurationException(
                message: "Error, the capacity of the quota `{$type}` must be not relative",
                code: 400,
            );
        }
        if (!$this->isSoft && $this->isRelative($this->require)) {
            throw new QuotaWrongConfigurationException(
                message: "Error, the requires capacity of the quota `{$type}` must be not relative",
                code: 400,
            );
        }

        if (empty($this->require)) {
            $this->require = $this->capacity;
        }

        $this->normalizedCapacity = $this->stringCapacityToValue($this->capacity);
        $this->normalizedRequire = $this->stringCapacityToValue($this->require);

        if (!$this->isRelative($this->capacity) && $this->normalizedCapacity < $this->normalizedRequire) {
            throw new QuotaWrongConfigurationException(
                message: "Error, the capacity of the quota `{$type}` must be bigger of require",
                code: 400,
            );
        }
    }

    abstract protected function stringCapacityToValue(string $capacity): int;

    abstract protected function valueToStringCapacity(int $value): string;

    protected function getRelativeValueFromCapacity(int $value): int
    {
        return (int) ($this->normalizedCapacity * $value / 100);
    }

    private function normalizedValue(string $capacity): string
    {
        return $this->valueToStringCapacity(
            $this->stringCapacityToValue(
                capacity: $capacity,
            )
        );
    }

    private function checkIfLimitIsValid(string $require, string $limit): bool
    {
        $requireValue = $this->stringCapacityToValue($require);
        $limitValue = $this->stringCapacityToValue($limit);

        return $requireValue <= $limitValue;
    }

    private function checkIfLower(int $from, string $capacity, int $numberOfReplicas): bool
    {
        if ($this->isValidRelative($capacity)) {
            $testValue = $this->getRelativeValueFromCapacity((int) $capacity) * $numberOfReplicas;
        } else {
            $testValue = $this->stringCapacityToValue($capacity) * $numberOfReplicas;
        }

        return $from >= $testValue;
    }

    private function subtractCapacity(string $capacity, int $numberOfReplicas): void
    {
        $currentValue = $this->normalizedCapacity;
        $testValue = $this->stringCapacityToValue($capacity) * $numberOfReplicas;

        $currentValue -= $testValue;

        $this->normalizedCapacity = $currentValue;
        $this->capacity = $this->valueToStringCapacity($currentValue);
    }

    private function subtractRequire(string $capacity, int $numberOfReplicas): void
    {
        $currentValue = $this->normalizedRequire;
        $testValue = $this->stringCapacityToValue($capacity) * $numberOfReplicas;

        $currentValue -= $testValue;

        $this->normalizedRequire = $currentValue;
        $this->require = $this->valueToStringCapacity($currentValue);
    }

    protected function isRelative(string $capacity): bool
    {
        return str_ends_with($capacity, '%');
    }

    private function isValidRelative(string $capacity): bool
    {
        if (!$this->isRelative($capacity)) {
            return false;
        }

        $capacityValue = (int) $capacity;
        if ($capacityValue > 100 || $capacity < 0) {
            throw new ResourceWrongConfigurationException(
                message: "Error {$capacity} is not a valid percentage, must be between 0-100",
                code: 400,
            );
        }

        return true;
    }

    private function getType(): string
    {
        return $this->type;
    }

    public function getCapacity(): string
    {
        return $this->capacity;
    }

    public function getRequire(): string
    {
        return $this->require;
    }

    public function update(AvailabilityInterface $availability): AvailabilityInterface
    {
        if ($availability::class !== static::class) {
            throw new QuotasNotCompliantException(
                message: sprintf(
                    "Error, `%s` and `%s` are not compliant",
                    $availability::class,
                    static::class,
                ),
                code: 500,
            );
        }

        if (!$this->checkIfLower($this->normalizedCapacity, $availability->getCapacity(), 1)) {
            throw new ResourceCapacityExceededException(
                message: sprintf(
                    "Error, the deployment quota definition exceed the available capacity `%s` for `%s`",
                    $this->getCapacity(),
                    $this->type,
                ),
                code: 400,
            );
        }

        return $availability;
    }

    public function reserve(
        string $require,
        string $limit,
        int $numberOfReplicas,
        ResourceSet $set,
    ): AvailabilityInterface {
        if (!$this->checkIfLimitIsValid($require, $limit)) {
            throw new ResourceWrongConfigurationException(
                message: sprintf(
                    "Error the limit `%s` for `%s` is lower than the require `%s`",
                    $limit,
                    $this->getType(),
                    $require,
                ),
                code: 400,
            );
        }

        if (!$this->checkIfLower($this->normalizedCapacity, $limit, $numberOfReplicas)) {
            $isSoftDefined = '';
            if ($this->isSoft) {
                $isSoftDefined = ' (soft defined limit)';
            }

            throw new ResourceCapacityExceededException(
                message: sprintf(
                    "Error, available capacity for `%s` is `%s`%s, but limited `%s`",
                    $this->type,
                    $this->getCapacity(),
                    $isSoftDefined,
                    $limit,
                ),
                code: 400,
            );
        }

        if (!$this->checkIfLower($this->normalizedRequire, $limit, $numberOfReplicas)) {
            $isSoftDefined = '';
            if ($this->isSoft) {
                $isSoftDefined = ' (soft defined limit)';
            }

            throw new ResourceCapacityExceededException(
                message: sprintf(
                    "Error, available requires capacity for `%s` is `%s`%s, but require `%s`",
                    $this->type,
                    $this->getRequire(),
                    $isSoftDefined,
                    $require,
                ),
                code: 400,
            );
        }

        $this->subtractRequire($require, $numberOfReplicas);
        $this->subtractCapacity($limit, $numberOfReplicas);

        $set->add(
            new Resource(
                $this->getType(),
                $this->normalizedValue($require),
                $this->normalizedValue($limit),
            ),
        );

        return $this;
    }

    public function updateResource(AutomaticResource $resource, int $limit): AvailabilityInterface
    {
        $resource->setLimit(
            require: $this->valueToStringCapacity(
                $this->getRelativeValueFromCapacity((int) ceil($limit * 0.10)),
            ),
            limit: $this->valueToStringCapacity(
                $this->getRelativeValueFromCapacity($limit),
            ),
        );

        return $this;
    }
}
