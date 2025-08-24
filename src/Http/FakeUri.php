<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use function rtrim;

use Psr\Http\Message\UriInterface;

/**
 * Fake implementation of {@see UriInterface} (PSR-7).
 *
 * This fake URI represents a PSR-7 URI for testing purposes. It allows configuration
 * and retrieval of scheme, host, path, port, user info, query, and fragment
 * without any real network resolution or DNS lookups.
 */
class FakeUri implements UriInterface
{
    /**
     * Creates a new fake uri instance.
     *
     * @param string   $scheme   URI scheme (default: "http").
     * @param string   $host     URI host name (default: "localhost").
     * @param string   $path     URI path component (default: "/").
     * @param int|null $port     URI port number or null if none.
     * @param string   $userInfo User info (e.g., "user:pass"), if any.
     * @param string   $query    URI query string.
     * @param string   $fragment URI fragment.
     */
    public function __construct(
        private string $scheme = 'http',
        private string $host = 'localhost',
        private string $path = '/',
        private ?int $port = null,
        private string $userInfo = '',
        private string $query = '',
        private string $fragment = '',
    ) {
        $this->query = rtrim($query, '?');
        $this->fragment = rtrim($fragment, '#');
    }

    /**
     * Returns the string representation of the URI.
     *
     * Combines all components into a full URI string.
     *
     * @return string The complete URI.
     */
    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . '://';
        }

        $uri .= $this->getAuthority();
        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= "?{$this->query}";
        }
        
        if ($this->fragment !== '') {
            $uri .= "#{$this->fragment}";
        }
        
        return $uri;
    }

    /**
     * Returns the URI scheme component.
     *
     * @return string The scheme without trailing colon.
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Returns the URI authority component: [user-info@]host[:port].
     *
     * @return string The authority part of the URI.
     */
    public function getAuthority(): string
    {
        $auth = $this->host;

        if ($this->userInfo !== '') {
            $auth = "{$this->userInfo}@{$auth}";
        }
        
        if ($this->port !== null) {
            $auth .= ":{$this->port}";
        }

        return $auth;
    }

    /**
     * Returns the user information component.
     *
     * @return string The user info (e.g., "user", "user:password").
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * Returns the host component.
     *
     * @return string The hostname or IP.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Returns the port number.
     *
     * @return int|null The port number or null if none set.
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * Returns the path component of the URI.
     *
     * @return string The URI path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Returns the query string component.
     *
     * @return string The query string without leading '?'.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Returns the fragment component.
     *
     * @return string The fragment without leading '#'.
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Returns a new instance with the specified scheme.
     *
     * @param string $scheme New scheme.
     * 
     * @return static The updated uri instance.
     */
    public function withScheme($scheme): static
    {
        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * Returns a new instance with the specified user info.
     *
     * @param string      $user     User name.
     * @param string|null $password Optional password.
     * 
     * @return static The updated uri instance. 
     */
    public function withUserInfo(string $user, $password = null): static
    {
        $clone = clone $this;
        $clone->userInfo = $password ? "{$user}:{$password}" : $user;

        return $clone;
    }

    /**
     * Returns a new instance with the specified host.
     *
     * @param string $host New host.
     * 
     * @return static The updated uri instance.
     */
    public function withHost($host): static
    {
        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * Returns a new instance with the specified port.
     *
     * @param int|null $port New port or null to remove.
     * 
     * @return static The updated uri instance.
     */
    public function withPort($port): static
    {
        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Returns a new instance with the specified path.
     *
     * @param string $path New path.
     * 
     * @return static The updated uri instance.
     */
    public function withPath($path): static
    {
        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * Returns a new instance with the specified query string.
     *
     * @param string $query New query string without leading '?'.
     * 
     * @return static The updated uri instance.
     */
    public function withQuery($query): static
    {
        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * Returns a new instance with the specified fragment.
     *
     * @param string $fragment New fragment without leading '#'.
     * 
     * @return static The updated uri instance.
     */
    public function withFragment($fragment): static
    {
        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }
}
