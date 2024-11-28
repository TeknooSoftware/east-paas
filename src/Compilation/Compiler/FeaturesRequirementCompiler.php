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

namespace Teknoo\East\Paas\Compilation\Compiler;

use InvalidArgumentException;
use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\FeaturesRequirement\Set;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Compilation\FeaturesRequirement\ValidatorInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

use function array_flip;

/**
 * Compiler to check if all features requirement to compile a PaaS file are validated
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class FeaturesRequirementCompiler implements CompilerInterface
{
    /**
     * @param ValidatorInterface[] $validator
     */
    public function __construct(
        private array $validator,
    ) {
        foreach ($this->validator as $validatorInstance) {
            if (!$validatorInstance instanceof ValidatorInterface) {
                throw new InvalidArgumentException(
                    'Invalid validator, it must be an instance of ' . ValidatorInterface::class
                );
            }
        }
    }

    public function addValidator(ValidatorInterface $validator): self
    {
        $this->validator[] = $validator;

        return $this;
    }

    public function compile(
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): CompilerInterface {
        if (empty($definitions)) {
            return $this;
        }

        $definitionsList = $definitions;
        $requirements = new Set(array_flip($definitionsList));

        foreach ($this->validator as $validator) {
            $validator($requirements);
        }

        $requirements->checkIfAllRequirementsAreValidated();

        return $this;
    }
}
