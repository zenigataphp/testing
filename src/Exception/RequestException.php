<?php

declare(strict_types=1);

namespace Zenigata\Testing\Exception;

use Exception;
use Throwable;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Zenigata\Testing\Http\FakeRequest;

/**
 * Fake implementation of {@see RequestExceptionInterface} (PSR-18).
*
 * Exception representing a failed HTTP request.
 * 
 * This exception encapsulates a {@see RequestInterface} instance associated 
 * with the failed operation, allowing inspection of the request that triggered 
 * the error. It is useful for simulating PSR-18 client errors in unit tests.
 */
class RequestException extends Exception implements RequestExceptionInterface
{
    /**
     * The request associated with this exception.
     *
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * Creates a new request exception instance.
     *
     * @param RequestInterface $request  The HTTP request that caused the exception.
     * @param string           $message  Optional exception message.
     * @param int              $code     Optional error code.
     * @param Throwable|null   $previous Optional previous throwable for chaining.
     */
    public function __construct(
        RequestInterface $request = new FakeRequest(),
        string $message = '',
        int $code = 0,
        Throwable|null $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}