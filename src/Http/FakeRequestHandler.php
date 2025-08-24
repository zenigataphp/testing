<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Fake implementation of {@see RequestHandlerInterface} (PSR-15).
 *
 * It optionally returns a preconfigured {@see ResponseInterface},
 * or throws a preconfigured {@see Throwable}, allowing testing of
 * request handling and exception propagation in a controlled way.
 * 
 * It provides overridable hook methods that let subclasses intercept the request/response 
 * lifecycle to add custom logic, which is particularly handy in testing
 * when using anonymous classes to reduce boilerplate code.
 */
class FakeRequestHandler implements RequestHandlerInterface
{
    /**
     * Creates a new fake request handler instance.
     *
     * @param ResponseInterface $response  Response to be returned when handling a request.
     * @param Throwable|null    $throwable Optional throwable to be thrown instead of returning a response.
     */
    public function __construct(
        private ResponseInterface $response = new FakeResponse(),
        private ?Throwable $throwable = null,
    ) {}

    /**
     * Handles the incoming server request.
     * 
     * Optionally returns the configured response or throws the configured throwable.
     *
     * @param ServerRequestInterface $request Incoming server request.
     *
     * @return ResponseInterface The configured response, unless an exception is thrown.
     * @throws Throwable If a throwable was configured for this handler.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->onHandle($request);

        if ($this->throwable !== null) {
            throw $this->throwable;
        }

        $this->onResponse($this->response);

        return $this->response;
    }

    /**
     * Hook invoked before handling the request.
     *
     * @param ServerRequestInterface $request The incoming request.
     * 
     * @return void
     */
    protected function onHandle(ServerRequestInterface $request): void
    {
        // Override in subclass to customize behavior
    }

    /**
     * Hook invoked before returning the response.
     *
     * @param ResponseInterface $response The outgoing response.
     * 
     * @return void
     */
    protected function onResponse(ResponseInterface $response): void
    {
        // Override in subclass to customize behavior
    }
}
