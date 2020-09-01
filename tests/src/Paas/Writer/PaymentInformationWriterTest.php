<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Paas\Writer;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Writer\WriterInterface;
use Teknoo\East\Paas\Object\Account;
use Teknoo\East\Paas\Object\PaymentInformation;
use Teknoo\East\Paas\Writer\PaymentInformationWriter;
use Teknoo\Tests\East\Website\Writer\PersistTestTrait;

/**
 * @covers \Teknoo\East\Paas\Writer\PaymentInformationWriter
 */
class PaymentInformationWriterTest extends TestCase
{
    use PersistTestTrait;

    public function buildWriter(): WriterInterface
    {
        return new PaymentInformationWriter($this->getObjectManager());
    }

    /**
     * @return PaymentInformation
     * @throws \Teknoo\States\Proxy\Exception\StateNotFound
     */
    public function getObject()
    {
        return new PaymentInformation('foo');
    }
}
