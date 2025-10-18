<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
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
 * Covered cases:
 *
 * - Default state.
 * - Delegation of request processing to the provided request handler.
 * - Return a default fake response when no custom response is provided.
 * - Return a custom response when injected via the constructor.
 * - Throw a preconfigured exception instead of returning a response.
 * - Correct execution of `onHandle()` and `onResponse()` hooks.
 */
#[CoversClass(FakeMiddleware::class)]
final class FakeMiddlewareTest extends TestCase
{
    public function testDefaults(): void
    {
        $middleware = new FakeMiddleware();

        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testPassRequestToNextHandler(): void
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

    public function testReturnResponseInterface(): void
    {
        $middleware = new FakeMiddleware();

        $response = $middleware->process(new FakeServerRequest(), new FakeRequestHandler());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testDelegateResponseToHandler(): void
    {
        $expectedResponse = new FakeResponse();
        $middleware = new FakeMiddleware();

        $response = $middleware->process(
            request: new FakeServerRequest(),
            handler: new FakeRequestHandler($expectedResponse)
        );

        $this->assertSame($expectedResponse, $response);
    }

    public function testReturnCustomResponseIfProvided(): void
    {
        $initialResponse = new FakeResponse(statusCode: 400);
        $middleware = new FakeMiddleware($initialResponse);

        $response = $middleware->process(
            request: new FakeServerRequest(),
            handler: new FakeRequestHandler()
        );

        $this->assertSame($initialResponse, $response);
    }

    public function testThrowExceptionIfProvided(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Custom exception');

        $handler = new FakeMiddleware(exception: new RuntimeException('Custom exception'));

        $handler->process(new FakeServerRequest(), new FakeRequestHandler());
    }

    public function testHooksCorrectExecution(): void
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