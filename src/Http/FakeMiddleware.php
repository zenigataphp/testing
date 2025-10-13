<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Fake implementation of {@see MiddlewareInterface} (PSR-15).
 *
 * It optionally returns a preconfigured {@see ResponseInterface},
 * or throws a preconfigured {@see Throwable}.
 * 
 * It provides overridable hook methods that let subclasses intercept the request/response
 * lifecycle to add custom logic, which is particularly handy in testing
 * when using anonymous classes to reduce boilerplate code.
 */
class FakeMiddleware implements MiddlewareInterface
{
    /**
     * Creates a new fake middleware instance.
     *
     * @param ResponseInterface|null $response  Optional response to be returned instead of handling a request.
     * @param Throwable|null         $exception Optional exception to be thrown instead of returning a response.
     */
    public function __construct(
        private ?ResponseInterface $response = null,
        private ?Throwable $exception = null,
    ) {}

    /**
     * Processes an incoming server request and delegates to the next handler.
     *
     * @param ServerRequestInterface  $request The incoming server request.
     * @param RequestHandlerInterface $handler Next request handler to delegate to.
     *
     * @return ResponseInterface The fake response instance.
     * @throws Throwable If an exception was configured in the constructor.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->onHandle($request, $handler);

        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->response === null) {
            $this->response = $handler->handle($request);
        }

        $this->onResponse($this->response, $handler);

        return $this->response;
    }

    /**
     * Hook invoked before delegating the request to the next handler.
     * 
     * @param ServerRequestInterface  $request The incoming request.
     * @param RequestHandlerInterface $handler The handler that will handle the request.
     */
    protected function onHandle(ServerRequestInterface $request, RequestHandlerInterface $handler): void
    {
        // Override in subclass to customize behavior
    }

    /**
     * Hook invoked before returning the response.
     * 
     * @param ResponseInterface       $response The outgoing response.
     * @param RequestHandlerInterface $handler  The handler that produced the response.
     */
    protected function onResponse(ResponseInterface $response, RequestHandlerInterface $handler): void 
    {
        // Override in subclass to customize behavior
    }
}
