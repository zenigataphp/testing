<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use Alexanderpas\Common\HTTP\ReasonPhrase;
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
     * The reason phrase associated with the HTTP status code.
     *
     * @var string
     */
    private string $reasonPhrase;

    /**
     * Creates a new fake response instance.
     *
     * @param int                    $statusCode   HTTP status code (default: 200).
     * @param string                 $reasonPhrase Optional reason phrase; if empty, the status code's default is used. (default: "").
     * @param array<string,string[]> $headers      HTTP headers (default: []).
     * @param mixed                  $body         Message body stream (default: null).
     * @param string                 $protocol     HTTP protocol version (default: "1.1").
     */
    public function __construct(
        private int $statusCode = 200,
        string $reasonPhrase = '',
        array $headers = [],
        mixed $body = null,
        string $protocol = '1.1',
    ) {
        if ($body !== null && !$body instanceof StreamInterface) {
            $body = new FakeStream($body);
        }

        parent::__construct($headers, $body, $protocol);

        $this->reasonPhrase = $reasonPhrase === ''
            ? ReasonPhrase::fromInteger($statusCode)->value
            : $reasonPhrase;

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
     * @param int    $statusCode   New HTTP status code.
     * @param string $reasonPhrase Optional reason phrase; if empty, the status code's default is used.
     * 
     * @return static The updated response instance.
     */
    public function withStatus($statusCode, $reasonPhrase = ''): static
    {
        if ($reasonPhrase === '') {
            $reasonPhrase = ReasonPhrase::fromInteger($statusCode)->value;
        }

        return new self(
            statusCode:   $statusCode,
            reasonPhrase: $reasonPhrase,
            headers:      $this->headers,
            body:         $this->body,
            protocol:     $this->protocol,
        );
    }
}