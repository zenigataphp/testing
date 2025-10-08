<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use function array_merge;
use function get_debug_type;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

use LogicException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Fake implementation of {@see MessageInterface} (PSR-7).
 *
 * This fake HTTP message represents the common parts of requests and responses,
 * including protocol version, headers, and body handling, without performing any actual I/O.
 * It allows you to simulate and inspect PSR-7 message behavior for testing purposes.
 *
 * The constructor validates that all provided headers are arrays of strings,
 * throwing a {@see LogicException} otherwise.
 */
class FakeMessage implements MessageInterface
{
    /**
     * @param array<string,string[]> $headers  Initial message headers as an associative array.
     * @param StreamInterface|null   $body     Optional message body stream.
     * @param string                 $protocol HTTP protocol version (default: "1.1").
     * 
     * @throws LogicException If any header value is not an array of strings.
     */
    public function __construct(
        protected array $headers = [],
        protected ?StreamInterface $body = null,
        protected string $protocol = '1.1'
    ) {
        foreach ($headers as $name => $value) {
            if (!is_array($value)) {
                throw new LogicException(sprintf(
                    "Header '%s' must be an array of strings, got %s.",
                    $name,
                    get_debug_type($value)
                ));
            }

            foreach ($value as $i => $v) {
                if (!is_string($v)) {
                    throw new LogicException(sprintf(
                        "Header '%s' expects strings, but index %d has type %s.",
                        $name,
                        $i,
                        get_debug_type($v)
                    ));
                }
            }
        }
    }

    /**
     * Returns the current HTTP protocol version string.
     *
     * @return string The current HTTP protocol version string (e.g., "1.1")
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * Returns a copy of the message with a different HTTP protocol version.
     *
     * @param string $version The new protocol version.
     * 
     * @return static The updated message instance.
     */
    public function withProtocolVersion($version): static
    {
        $clone = clone $this;
        $clone->protocol = $version;

        return $clone;
    }

    /**
     * Returns all headers as an associative array: name => array of values.
     *
     * @return array<string,string[]> The headers array.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Checks if the message contains the given header name.
     *
     * @param string $name The header name.
     * 
     * @return bool True if the header exists, false otherwise.
     */
    public function hasHeader($name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * Returns all values for the given header name, or an empty array if not set.
     *
     * @param string $name The header name.
     * 
     * @return string[] Header values as array of string.
     */
    public function getHeader($name): array
    {
        return $this->headers[$name] ?? [];
    }

    /**
     * Returns all values for the given header as a single comma-separated string.
     *
     * @param string $name The header name.
     * 
     * @return string Comma-separated header values, or an empty string if not present.
     */
    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * Returns a copy of the message with the given header replaced by the provided value(s).
     *
     * @param string          $name  The header name.
     * @param string|string[] $value One or more header values.
     * 
     * @return static The updated message instance.
     */
    public function withHeader($name, $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = (array) $value;

        return $clone;
    }

    /**
     * Returns a copy of the message with the given header value(s) appended.
     *
     * @param string          $name  The eader name.
     * @param string|string[] $value One or more values to append.
     * 
     * @return static The updated message instance.
     */
    public function withAddedHeader($name, $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = array_merge(
            $clone->headers[$name] ?? [],
            (array) $value
        );

        return $clone;
    }

    /**
     * Returns a copy of the message without the specified header.
     *
     * @param string $name The header name to remove.
     * 
     * @return static The updated message instance.
     */
    public function withoutHeader($name): static
    {
        $clone = clone $this;
        unset($clone->headers[$name]);

        return $clone;
    }

    /**
     * Returns the message body as a stream.
     *
     * If no body is set, a new empty {@see FakeStream} is created automatically.
     *
     * @return StreamInterface The message body stream.
     */
    public function getBody(): StreamInterface
    {
        return $this->body ??= new FakeStream();
    }

    /**
     * Returns a copy of the message with a different body stream.
     *
     * @param StreamInterface $body The new message body.
     * 
     * @return static The updated message instance.
     */
    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }
}
