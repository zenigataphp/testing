<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Fake implementation of {@see ServerRequestInterface} (PSR-7).
 *
 * This fake server request represents an incoming HTTP request for testing purposes,
 * allowing inspection and modification of server parameters, cookies, query parameters,
 * uploaded files, parsed body, and request attributes without any real HTTP processing.
 */
class FakeServerRequest extends FakeRequest implements ServerRequestInterface
{
    /**
     * Creates a new fake server request instance.
     *
     * @param array                $serverParams  Server parameters.
     * @param array                $cookieParams  Cookies sent by the client.
     * @param array                $queryParams   Query string parameters.
     * @param array                $uploadedFiles Uploaded files, as an array of UploadedFileInterface.
     * @param mixed                $parsedBody    Deserialized request body content.
     * @param array                $attributes    Custom request attributes.
     * @param string               $method        HTTP method (default: "GET").
     * @param string               $requestTarget Request target, path or full URI (default: "/").
     * @param UriInterface         $uri           Request URI.
     * @param array                $headers       HTTP headers.
     * @param StreamInterface|null $body          Message body stream.
     * @param string               $protocol      HTTP protocol version (default: "1.1").
     */
    public function __construct(
        private array $serverParams = [],
        private array $cookieParams = [],
        private array $queryParams = [],
        private array $uploadedFiles = [],
        private mixed $parsedBody = null,
        private array $attributes = [],
        string $method = 'GET',
        private string $requestTarget = '/',
        UriInterface $uri = new FakeUri(),
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocol = '1.1',
    ) {
        parent::__construct($method, $uri, $requestTarget, $headers, $body, $protocol);
    }

    /**
     * Returns server parameters.
     *
     * @return array Associative array of server parameters.
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * Returns all cookies sent by the client.
     *
     * @return array Associative array of cookie names to values.
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * Returns a new instance with the specified cookies.
     *
     * @param array $cookies Associative array of cookie names to values.
     * 
     * @return static The updated server request instance.
     */
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    /**
     * Returns the query string parameters.
     *
     * @return array Associative array of query parameter names to values.
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Returns a new instance with the specified query string parameters.
     *
     * @param array $query Associative array of query parameter names to values.
     * 
     * @return static The updated server request instance.
     */
    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    /**
     * Returns all uploaded files associated with the request.
     *
     * @return array Array of {@see UploadedFileInterface} instances.
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Returns a new instance with the specified uploaded files.
     *
     * @param array $uploadedFiles Array of {@see UploadedFileInterface} instances.
     * 
     * @return static The updated server request instance.
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    /**
     * Returns the deserialized request body (e.g., from JSON or form data).
     *
     * @return mixed The parsed body content, or null if not set.
     */
    public function getParsedBody(): mixed
    {
        return $this->parsedBody;
    }

    /**
     * Returns a new instance with the specified parsed body content.
     *
     * @param mixed $data The deserialized body data.
     * 
     * @return static The updated server request instance.
     */
    public function withParsedBody($data): static
    {
        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    /**
     * Returns all request attributes.
     *
     * @return array Associative array of attribute names to values.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Retrieves a specific request attribute.
     *
     * @param string $name    Attribute name.
     * @param mixed  $default Default value if attribute is not present.
     * 
     * @return mixed The attribute value or the default if not found.
     */
    public function getAttribute($name, $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Returns a new instance with the specified attribute set.
     *
     * @param string $name  Attribute name.
     * @param mixed  $value Attribute value.
     * 
     * @return static The updated server request instance.
     */
    public function withAttribute($name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    /**
     * Returns a new instance without the specified attribute.
     *
     * @param string $name Attribute name to remove.
     * 
     * @return static The updated server request instance.
     */
    public function withoutAttribute($name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }
}
