<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Infrastructures\Doctrine\Repository\ODM;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\DBSource\RepositoryInterface;
use Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\PaymentInformationRepository;
use Teknoo\Tests\East\Website\Doctrine\DBSource\ODM\RepositoryTestTrait;

/**
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Paas\Infrastructures\Doctrine\Repository\ODM\PaymentInformationRepository
 */
class PaymentInformationRepositoryTest extends TestCase
{
    use RepositoryTestTrait;

    /**
     * @inheritDoc
     */
    public function buildRepository(): RepositoryInterface
    {
        return new PaymentInformationRepository($this->getDoctrineObjectRepositoryMock());
    }
}
