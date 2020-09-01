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

namespace Teknoo\East\Paas\Infrastructures\Docker;

use Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper\Generator;
use Teknoo\East\Paas\Infrastructures\Docker\BuilderWrapper\Running;
use Teknoo\East\Paas\Infrastructures\Docker\Contracts\ProcessFactoryInterface;
use Teknoo\East\Paas\Infrastructures\Docker\Contracts\ScriptWriterInterface;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Conductor\CompiledDeployment;
use Teknoo\East\Paas\Container\Image;
use Teknoo\East\Paas\Container\Volume;
use Teknoo\East\Paas\Contracts\Container\BuilderInterface;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Object\XRegistryAuth;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyInterface;
use Teknoo\States\Proxy\ProxyTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class BuilderWrapper implements BuilderInterface, ProxyInterface, AutomatedInterface
{
    use ProxyTrait;
    use AutomatedTrait {
        AutomatedTrait::updateStates insteadof ProxyTrait;
    }

    private const GRACEFULTIME = 30;

    private string $binary;

    /**
     * @var array<string, string>
     */
    private array $templates;

    private ProcessFactoryInterface $processFactory;

    private ScriptWriterInterface $scriptWriter;

    private string $mountSuffix;

    private ?int $timeout;

    private ?string $url = null;

    private ?XRegistryAuth $auth = null;

    /**
     * @param array<string, string> $templates
     */
    public function __construct(
        string $binary,
        array $templates,
        ProcessFactoryInterface $processFactory,
        ?int $timeout,
        ScriptWriterInterface $scriptWriter,
        string $mountSuffix
    ) {
        if (empty($templates['image'])) {
            throw new \DomainException('Missing image template');
        }

        if (empty($templates['volume'])) {
            throw new \DomainException('Missing image template');
        }

        $this->binary = $binary;
        $this->templates = $templates;
        $this->processFactory = $processFactory;
        $this->timeout = $timeout;
        $this->scriptWriter = $scriptWriter;
        $this->mountSuffix = $mountSuffix;

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
                ->with('url', new Property\IsNotEmpty()),

            (new Property(Generator::class))
                ->with('url', new Property\IsEmpty()),
        ];
    }

    public function configure(string $url, ?IdentityInterface $auth): BuilderInterface
    {
        if (null !== $auth && !$auth instanceof XRegistryAuth) {
            throw new \RuntimeException('Not Supported');
        }

        $that = clone $this;
        $that->url = $url;
        $that->auth = $auth;

        $that->updateStates();

        return $that;
    }

    public function buildImages(
        CompiledDeployment $compiledDeployment,
        PromiseInterface $promise
    ): BuilderInterface {
        $this->setTimeout();

        $processes = [];
        $compiledDeployment->foreachImage(
            function (Image $image) use (&$processes, $compiledDeployment) {
                //Prefix image with the repository url
                $newImage = $image->updateUrl((string) $this->getUrl());
                $compiledDeployment->updateImage($image, $newImage);

                $scriptFileName = $this->generateShellScriptForImage($newImage);

                $process = ($this->processFactory)([$scriptFileName], $newImage->getPath());

                $this->startProcess($process, $newImage->getVariables());

                $processes[] = $process;
            }
        );

        $this->waitProcess($processes, $promise);

        return $this;
    }

    public function buildVolumes(
        CompiledDeployment $compiledDeployment,
        string $workingPath,
        PromiseInterface $promise
    ): BuilderInterface {
        $this->setTimeout();

        $processes = [];
        $compiledDeployment->foreachVolume(
            function (string $name, Volume $volume) use (&$processes, $workingPath, $compiledDeployment) {
                //Prefix image with the repository url
                $newVolume = $volume->updateUrl((string) $this->getUrl());
                $newVolume = $newVolume->updateMountPath(
                    \rtrim($volume->getTarget(), '/') . $this->mountSuffix
                );
                $compiledDeployment->defineVolume($name, $newVolume);

                $scriptFileName = $this->generateShellScriptForVolume($newVolume, $workingPath);

                $process = ($this->processFactory)([$scriptFileName], $workingPath);

                $this->startProcess($process, []);

                $processes[] = $process;
            }
        );

        $this->waitProcess($processes, $promise);

        return $this;
    }
}