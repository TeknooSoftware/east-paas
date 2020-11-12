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

namespace Teknoo\East\Paas\Infrastructures\Guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class AsyncResponse implements ResponseInterface
{
    private PromiseInterface $promise;

    private ?ResponseInterface $response = null;

    public function __construct(PromiseInterface $promise)
    {
        $this->promise = $promise;
    }

    private function getResponse(): ResponseInterface
    {
        if (null !== $this->response) {
            return $this->response;
        }

        return $this->response = $this->promise->wait();
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->getResponse()->getProtocolVersion();
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {
        throw new \LogicException('Not available here');
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->getResponse()->getHeaders();
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        return $this->getResponse()->hasHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        return $this->getResponse()->getHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        return $this->getResponse()->getHeaderLine($name);
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        throw new \LogicException('Not available here');
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        throw new \LogicException('Not available here');
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        throw new \LogicException('Not available here');
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->getResponse()->getBody();
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        throw new \LogicException('Not available here');
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode()
    {
        return $this->getResponse()->getStatusCode();
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        throw new \LogicException('Not available here');
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase()
    {
        return $this->getResponse()->getReasonPhrase();
    }
}
