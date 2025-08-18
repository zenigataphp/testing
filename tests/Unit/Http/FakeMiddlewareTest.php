<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zenigata\Testing\Http\FakeMiddleware;
use Zenigata\Testing\Http\FakeRequestHandler;
use Zenigata\Testing\Http\FakeResponse;
use Zenigata\Testing\Http\FakeServerRequest;

/**
 * Unit test for {@see FakeMiddleware}.
 *
 * Verifies the behavior of the fake PSR-15 middleware implementation, covering:
 *
 * - Default instantiation and type compliance.
 * - Delegation of request processing to the provided request handler.
 * - Return type compliance with {@see ResponseInterface}.
 * - Correct propagation of a custom response returned by the handler.
 * - Correct execution of `onHandle()` and `onResponse()` hooks.
 */
#[CoversClass(FakeMiddleware::class)]
final class FakeMiddlewareTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $middleware = new FakeMiddleware();

        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    #[Test]
    public function passRequestToNextHandler(): void
    {
        $handler = new class extends FakeRequestHandler {
            public ?ServerRequestInterface $request = null;

            protected function onHandle(ServerRequestInterface $request): void
            {
                $this->request = $request;
            }
        };

        $request = new FakeServerRequest();
        $middleware = new FakeMiddleware();

        $middleware->process($request, $handler);

        $this->assertSame($request, $handler->request);
    }

    #[Test]
    public function returnResponseInterface(): void
    {
        $middleware = new FakeMiddleware();

        $response = $middleware->process(new FakeServerRequest(), new FakeRequestHandler());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    #[Test]
    public function delegateToHandler(): void
    {
        $expectedResponse = new FakeResponse();
        $middleware = new FakeMiddleware();

        $response = $middleware->process(
            request: new FakeServerRequest(),
            handler: new FakeRequestHandler($expectedResponse)
        );

        $this->assertSame($expectedResponse, $response);
    }

    #[Test]
    public function hooksCorrectExecution(): void
    {
        $middleware = new class extends FakeMiddleware {
            public ?RequestHandlerInterface $handler = null;
            public ?ServerRequestInterface $request = null;
            public ?ResponseInterface $response = null;

            protected function onHandle(ServerRequestInterface $request, RequestHandlerInterface $handler): void
            {
                $this->handler = $handler;
                $this->request = $request;
            }

            protected function onResponse(ResponseInterface $response, RequestHandlerInterface $handler): void
            {
                $this->response = $response;
            }
        };

        $handler = new FakeRequestHandler();
        $request = new FakeServerRequest();

        $response = $middleware->process($request, $handler);

        $this->assertSame($handler, $middleware->handler);
        $this->assertSame($request, $middleware->request);
        $this->assertSame($response, $middleware->response);
    }
}