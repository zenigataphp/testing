<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Fake implementation of {@see ResponseInterface} (PSR-7).
 *
 * This class simulates a PSR-7 response object, allowing configuration and retrieval
 * of HTTP status code, reason phrase, headers, body, and protocol version.
 * It is intended to be used as a test double without any real network operations.
 */
class FakeResponse extends FakeMessage implements ResponseInterface
{
    /**
     * Creates a new fake response instance.
     *
     * @param int                  $statusCode   HTTP status code (default: 200).
     * @param string               $reasonPhrase Reason phrase corresponding to the status code (default: "OK").
     * @param array                $headers      HTTP headers.
     * @param StreamInterface|null $body         Message body stream.
     * @param string               $protocol     HTTP protocol version (default: "1.1").
     */
    public function __construct(
        private int $statusCode = 200,
        private string $reasonPhrase = 'OK',
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocol = '1.1',
    ) {
        parent::__construct($headers, $body, $protocol);
    }

    /**
     * Returns the HTTP status code of the response.
     *
     * @return int The current status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the reason phrase associated with the status code.
     *
     * @return string The reason phrase (e.g., "OK", "Not Found").
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Returns a new instance with the specified status code and optional reason phrase.
     *
     * @param int    $code         New HTTP status code.
     * @param string $reasonPhrase Optional reason phrase; if empty, the status code's default is used.
     * 
     * @return static The updated response instance.
     */
    public function withStatus($code, $reasonPhrase = ''): static
    {
        return new self(
            statusCode: $code,
            reasonPhrase: $reasonPhrase,
            headers: $this->headers,
            body: $this->body,
            protocol: $this->protocol,
        );
    }
}