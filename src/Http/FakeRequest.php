<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Fake implementation of {@see RequestInterface} (PSR-7).
 *
 * This fake request represents an HTTP request for testing purposes,
 * allowing inspection and modification of method, URI, target, headers,
 * and body without performing any network operations.
 */
class FakeRequest extends FakeMessage implements RequestInterface
{
    /**
     * Creates a new fake HTTP request instance.
     *
     * @param string                  $method        HTTP method (default: "GET").
     * @param UriInterface            $uri           Request URI.
     * @param string                  $requestTarget Request target, path or full URI (default: "/").
     * @param array<string,string[]> $headers       Initial headers.
     * @param StreamInterface|null    $body          Optional message body stream.
     * @param string                  $protocol      HTTP protocol version (default: "1.1").
     */
    public function __construct(
        private string $method = 'GET',
        private UriInterface $uri = new FakeUri(),
        private string $requestTarget = '/',
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocol = '1.1',
    ) {
        parent::__construct($headers, $body, $protocol);
    }

    /**
     * Returns the request target (usually the path and query).
     *
     * @return string The request target.
     */
    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    /**
     * Returns a copy of the request with the specified request target.
     *
     * @param string $requestTarget The new request target.
     * 
     * @return static The updated request instance.
     */
    public function withRequestTarget($requestTarget): static
    {
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    /**
     * Returns the HTTP method for this request.
     *
     * @return string HTTP method (e.g., "GET", "POST").
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Returns a copy of the request with the specified HTTP method.
     *
     * @param string $method The new HTTP method.
     * 
     * @return static The updated request instance.
     */
    public function withMethod($method): static
    {
        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    /**
     * Returns the request URI.
     *
     * @return UriInterface The request URI.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns a copy of the request with the specified URI.
     *
     * @param UriInterface $uri          The new request URI.
     * @param bool         $preserveHost Whether to keep the current "Host" header if present.
     * 
     * @return static The updated request instance.
     */
    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost) {
            $clone->headers['Host'] = [$uri->getHost()];
        }

        return $clone;
    }
}
