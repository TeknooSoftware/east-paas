<?php

declare(strict_types=1);

/*
 * @copyright   Copyright (c) 2009-2020 Richard Déloge (richarddeloge@gmail.com)
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Paas\Infrastructures\Guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

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
