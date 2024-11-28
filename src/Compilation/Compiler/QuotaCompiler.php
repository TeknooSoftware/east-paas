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

use SensitiveParameter;
use Teknoo\East\Paas\Compilation\CompiledDeployment\Value\DefaultsBag;
use Teknoo\East\Paas\Compilation\Compiler\Quota\Factory as QuotaFactory;
use Teknoo\East\Paas\Contracts\Compilation\CompiledDeploymentInterface;
use Teknoo\East\Paas\Contracts\Compilation\CompilerInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;

/**
 * Compiler to manage soft quota, defined by user in a deployment file. Quota soft defined must be smaller than hard
 * quota defined in the Account, passed to the Job instance.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class QuotaCompiler implements CompilerInterface
{
    private const KEY_CATEGORY = 'category';

    private const KEY_TYPE = 'type';

    private const KEY_CAPACITY = 'capacity';

    private const KEY_REQUIRES = 'requires';

    public function __construct(
        private QuotaFactory $factory,
    ) {
    }

    public function compile(
        #[SensitiveParameter] array &$definitions,
        CompiledDeploymentInterface $compiledDeployment,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
        #[SensitiveParameter] JobUnitInterface $job,
        ResourceManager $resourceManager,
        DefaultsBag $defaultsBag,
    ): CompilerInterface {
        foreach ($definitions as $availability) {
            $requires = $availability[self::KEY_CAPACITY];
            if (!empty($availability[self::KEY_REQUIRES])) {
                $requires = $availability[self::KEY_REQUIRES];
            }

            $resourceManager->updateQuotaAvailability(
                $availability[self::KEY_TYPE],
                $this->factory->create(
                    category: $availability[self::KEY_CATEGORY],
                    type: $availability[self::KEY_TYPE],
                    capacity: (string) $availability[self::KEY_CAPACITY],
                    requires: (string) $requires,
                    isSoft: true,
                )
            );
        }

        $resourceManager->freeze();

        return $this;
    }
}
