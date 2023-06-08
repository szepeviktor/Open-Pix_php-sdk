<?php

namespace OpenPix\PhpSdk;

use DateTime;
use DateTimeInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Fluent builder for API requests.
 */
class Request
{
    private string $path;

    private string $method;

    private array $queryParams = [];

    /**
     * @var array|\Psr\Http\Message\StreamInterface|null
     */
    private $body = null;

    public function method(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function path(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function queryParams(array $queryParams): self
    {
        $this->queryParams = $queryParams;
        return $this;
    }

    /**
     * @var array|\Psr\Http\Message\StreamInterface|string $body
     */
    public function body($body): self
    {
        $this->body = $body;
        return $this;
    }

    public function pagination(int $skip, int $limit): self
    {
        $this->queryParams["skip"] = $skip;
        $this->queryParams["limit"] = $limit;
        return $this;
    }

    public function build(
        string $baseUri,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ): RequestInterface {
        $uri = $baseUri . $this->path;

        if (!empty($this->queryParams)) {
            $uri .= "?" . http_build_query($this->queryParams);
        }

        $request = $requestFactory->createRequest($this->method, $uri);

        $request = $this->injectBody($request, $streamFactory);

        return $request;
    }

    private function injectBody(RequestInterface $request, StreamFactoryInterface $streamFactory): RequestInterface
    {
        if (is_null($this->body)) {
            return $request;
        }

        if (!($this->body instanceof StreamInterface)) {
            $stream = $streamFactory->createStream(json_encode($this->body, JSON_THROW_ON_ERROR));

            return $request->withAddedHeader("Content-type", "application/json")
                ->withBody($stream);
        }

        return $request->withBody($this->body);
    }
}
