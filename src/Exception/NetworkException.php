<?php

declare(strict_types=1);

namespace Zenigata\Testing\Exception;

use Exception;
use Throwable;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Zenigata\Testing\Http\FakeRequest;

/**
 * Exception representing a network-level failure during an HTTP request.
 *
 * Fake implementation of {@see NetworkExceptionInterface} (PSR-18).
 * 
 * This exception indicates that the request could not be sent due to network issues 
 * such as connectivity errors or timeouts. The associated {@see RequestInterface} 
 * instance is stored for inspection.
 */
class NetworkException extends Exception implements NetworkExceptionInterface
{
    /**
     * The request associated with this exception.
     *
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * Creates a new network exception instance.
     *
     * @param RequestInterface $request  The HTTP request that failed to be sent.
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