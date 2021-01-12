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

namespace Teknoo\Tests\East\Paas\Infrastructures\Kubernetes;

use Maclof\Kubernetes\Client as KubernetesClient;
use Maclof\Kubernetes\Repositories\IngressRepository;
use Maclof\Kubernetes\Repositories\SecretRepository;
use Teknoo\East\Paas\Container\Expose\Ingress;
use Teknoo\East\Paas\Container\Expose\IngressPath;
use Teknoo\East\Paas\Container\Secret;
use Teknoo\East\Paas\Container\SecretReference;
use Teknoo\East\Paas\Container\Volume\PersistentVolume;
use Teknoo\East\Paas\Container\Volume\SecretVolume;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Client;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\ClientFactoryInterface;
use Maclof\Kubernetes\Client as KubeClient;
use Maclof\Kubernetes\Repositories\ReplicationControllerRepository;
use Maclof\Kubernetes\Repositories\ServiceRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Promise\PromiseInterface;
use Teknoo\East\Paas\Contracts\Conductor\CompiledDeploymentInterface;
use Teknoo\East\Paas\Container\Container;
use Teknoo\East\Paas\Container\Image\Image;
use Teknoo\East\Paas\Container\Pod;
use Teknoo\East\Paas\Container\Expose\Service;
use Teknoo\East\Paas\Container\Volume\Volume;
use Teknoo\East\Paas\Contracts\Object\IdentityInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\DeploymentInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\ExposingInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberCollectionInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\Contracts\Transcriber\TranscriberInterface;
use Teknoo\East\Paas\Infrastructures\Kubernetes\TranscriberCollection;
use Teknoo\East\Paas\Object\ClusterCredentials;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Kubernetes\TranscriberCollection
 */
class TranscriberCollectionTest extends TestCase
{
    public function testAdd()
    {
        self::assertInstanceOf(
            TranscriberCollection::class,
            (new TranscriberCollection())->add(
                1,
                $this->createMock(TranscriberInterface::class)
            )
        );
    }

    public function testGetIterator()
    {
        $collection = new TranscriberCollection();

        $c1 = $this->createMock(TranscriberInterface::class);
        $c2 = $this->createMock(TranscriberInterface::class);
        $c3 = $this->createMock(TranscriberInterface::class);

        $collection->add(2, $c1);
        $collection->add(1, $c2);
        $collection->add(3, $c3);

        $iterator = $collection->getIterator();
        self::assertSame(
            [$c2, $c1, $c3],
            \iterator_to_array($iterator)
        );
    }
}