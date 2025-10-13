<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Fake implementation of {@see ClientInterface} (PSR-18).
 * 
 * This fake HTTP client allows simulation of PSR-18 request/response behavior.
 * Instead of performing real network I/O, it records all sent requests
 * and optionally throws or returns predefined responses.
 */
class FakeHttpClient implements ClientInterface
{
    /**
     * List of all requests sent through this fake client.
     *
     * @var RequestInterface[]
     */
    public array $calls = [];

    /**
     * Creates a new fake HTTP client instance.
     *
     * @param ResponseInterface             $response  Predefined response to return for all requests.
     * @param ClientExceptionInterface|null $exception Optional exception to throw instead of returning a response.
     */
    public function __construct(
        private ResponseInterface $response = new FakeResponse(),
        private ?ClientExceptionInterface $exception = null,
    ) {}

    /**
     * Sends a request and returns a predefined response.
     *
     * This method simulates the behavior of a real PSR-18 client.
     * It records the sent request for later inspection and either.
     *
     * @param RequestInterface $request The request being "sent".
     * 
     * @return ResponseInterface The fake response instance.
     * @throws ClientExceptionInterface If an exception was configured in the constructor.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        $this->calls[] = $request;

        return $this->response;
    }
}