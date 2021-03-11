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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Git;

use GitWrapper\GitWrapper;
use Teknoo\East\Foundation\Promise\PromiseInterface;
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
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/paas Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Hook implements HookInterface, HookAwareInterface, ProxyInterface, AutomatedInterface, ImmutableInterface
{
    use ImmutableTrait;
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    /**
     * @var GitWrapper
     */
    private $gitWrapper;

    private ?string $path = null;

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    private ?JobUnitInterface $jobUnit = null;

    private ?JobWorkspaceInterface $workspace = null;

    /**
     * @param GitWrapper $gitWrapper
     */
    public function __construct($gitWrapper)
    {
        $this->uniqueConstructorCheck();

        $this->gitWrapper = $gitWrapper;

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
                ->with('options', new Property\HasNotEmptyValueForKey('key'))
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
                ->with('options', new Property\HasEmptyValueForKey('key')),
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

        if (!empty($this->gitWrapper)) {
            $this->gitWrapper = clone $this->gitWrapper;
        }

        $this->updateStates();
    }

    public function setContext(JobUnitInterface $jobUnit, JobWorkspaceInterface $workspace): HookAwareInterface
    {
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
