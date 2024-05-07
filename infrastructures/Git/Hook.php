<?php

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

declare(strict_types=1);

namespace Teknoo\East\Paas\Infrastructures\Git;

use SensitiveParameter;
use Symfony\Component\Process\Process;
use Teknoo\Recipe\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Hook\HookAwareInterface;
use Teknoo\East\Paas\Contracts\Hook\HookInterface;
use Teknoo\East\Paas\Contracts\Job\JobUnitInterface;
use Teknoo\East\Paas\Contracts\Workspace\JobWorkspaceInterface;
use Teknoo\East\Paas\Infrastructures\Git\Hook\Generator;
use Teknoo\East\Paas\Infrastructures\Git\Hook\Running;
use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Hook implements HookInterface, HookAwareInterface, AutomatedInterface, ImmutableInterface
{
    use ImmutableTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private ?string $path = null;

    /**
     * @var array<string, string>
     */
    private array $options = [];

    private ?JobUnitInterface $jobUnit = null;

    private ?JobWorkspaceInterface $workspace = null;

    public function __construct(
        private Process $gitProcess,
        private readonly string $privateKeyFilename,
    ) {
        $this->uniqueConstructorCheck();

        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
          Generator::class,
          Running::class,
        ];
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property(Running::class))
                ->with('options', new Property\IsNotEmpty())
                ->with('options', new Property\HasNotEmptyValueForKey('url'))
                ->with('options', new Property\HasNotEmptyValueForKey('path'))
                ->with('path', new Property\IsNotEmpty())
                ->with('jobUnit', new Property\IsNotEmpty())
                ->with('workspace', new Property\IsNotEmpty()),

            (new Property(Generator::class))
                ->with('options', new Property\IsEmpty()),
            (new Property(Generator::class))
                ->with('options', new Property\HasEmptyValueForKey('url')),
            (new Property(Generator::class))
                ->with('options', new Property\HasEmptyValueForKey('path')),
            (new Property(Generator::class))
                ->with('path', new Property\IsEmpty()),
            (new Property(Generator::class))
                ->with('jobUnit', new Property\IsEmpty()),
            (new Property(Generator::class))
              ->with('workspace', new Property\IsEmpty()),
        ];
    }

    public function __clone()
    {
        $this->options = [];
        $this->path = null;
        $this->jobUnit = null;
        $this->workspace = null;

        $this->gitProcess = clone $this->gitProcess;

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
