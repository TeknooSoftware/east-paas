<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Git;

use SensitiveParameter;
use Teknoo\East\Paas\Infrastructures\Git\Contracts\ProcessFactoryInterface;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Hook\HookAwareInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook\Generator;
use Teknoo\East\Paas\Infrastructures\Git\Hook\Running;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\States\Attributes\Assertion\Property;
use Teknoo\States\Attributes\StateClass;
use Teknoo\States\Automated\Assertion\Property\HasEmptyValueForKey;
use Teknoo\States\Automated\Assertion\Property\HasNotEmptyValueForKey;
use Teknoo\States\Automated\Assertion\Property\IsEmpty;
use Teknoo\States\Automated\Assertion\Property\IsNotEmpty;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[StateClass(Generator::class)]
#[StateClass(Running::class)]
#[Property(
    Running::class,
    ['options', IsNotEmpty::class],
    ['options', HasNotEmptyValueForKey::class, 'url'],
    ['options', HasNotEmptyValueForKey::class, 'path'],
    ['path', IsNotEmpty::class],
    ['jobUnit', IsNotEmpty::class],
    ['workspace', IsNotEmpty::class],
)]
#[Property(Generator::class, ['options', IsEmpty::class])]
#[Property(Generator::class, ['options', HasEmptyValueForKey::class, 'url'])]
#[Property(Generator::class, ['options', HasEmptyValueForKey::class, 'path'])]
#[Property(Generator::class, ['path', IsEmpty::class])]
#[Property(Generator::class, ['jobUnit', IsEmpty::class])]
#[Property(Generator::class, ['workspace', IsEmpty::class])]
class Hook implements HookInterface, HookAwareInterface, AutomatedInterface, ImmutableInterface
{
    use ImmutableTrait;
    use ProxyTrait;
    use AutomatedTrait;

    private ?string $path = null;

    /**
     * @var array<string, string>
     */
    private array $options = [];

    private ?JobUnitInterface $jobUnit = null;

    private ?JobWorkspaceInterface $workspace = null;

    public function __construct(
        private ProcessFactoryInterface $gitProcessFactory,
        private readonly string $privateKeyFilename,
    ) {
        $this->uniqueConstructorCheck();

        $this->initializeStateProxy();
        $this->updateStates();
    }

    public function __clone()
    {
        $this->options = [];
        $this->path = null;
        $this->jobUnit = null;
        $this->workspace = null;

        $this->updateStates();
    }

    public function setContext(
        #[SensitiveParameter] JobUnitInterface $jobUnit,
        #[SensitiveParameter] JobWorkspaceInterface $workspace,
    ): HookAwareInterface {
        $this->update('jobUnit', $jobUnit);
        $this->update('workspace', $workspace);

        return $this;
    }

    public function setPath(string $path): HookInterface
    {
        $this->update('path', $path);

        return $this;
    }

    public function setOptions(array $options, PromiseInterface $promise): HookInterface
    {
        $this->update('options', $options);

        $promise->success();

        return $this;
    }

    public function run(PromiseInterface $promise): HookInterface
    {
        $this->prepareThenClone($promise);

        return $this;
    }
}
