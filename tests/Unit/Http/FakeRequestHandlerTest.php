<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Zenigata\Testing\Http\FakeRequestHandler;
use Zenigata\Testing\Http\FakeResponse;
use Zenigata\Testing\Http\FakeServerRequest;

/**
 * Unit test for {@see FakeRequestHandler}.
 *
 * Verifies the behavior of the fake PSR-15 request handler implementation.
 * 
 * Covered cases:
 *
 * - Default instantiation as a valid {@see RequestHandlerInterface}.
 * - Return a default fake response when no custom response is provided.
 * - Return a custom response when injected via the constructor.
 * - Throw a preconfigured exception instead of returning a response.
 * - Correct execution of `onHandle()` and `onResponse()` hooks.
 */
#[CoversClass(FakeRequestHandler::class)]
final class FakeRequestHandlerTest extends TestCase
{
    public function testDefaults(): void
    {
        $handler = new FakeRequestHandler();

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
    }

    public function testReturnResponse(): void
    {
        $handler = new FakeRequestHandler();

        $response = $handler->handle(new FakeServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(FakeResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testReturnCustomResponseIfProvided(): void
    {
        $initialResponse = new FakeResponse();
        $handler = new FakeRequestHandler($initialResponse);

        $response = $handler->handle(new FakeServerRequest());

        $this->assertSame($initialResponse, $response);
    }

    public function testThrowExceptionIfProvided(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Custom exception');

        $handler = new FakeRequestHandler(throwable: new RuntimeException('Custom exception'));

        $handler->handle(new FakeServerRequest());
    }

    public function testHooksCorrectExecution(): void
    {
        $handler = new class extends FakeRequestHandler {
            public ?ServerRequestInterface $request = null;
            public ?ResponseInterface $response = null;

            protected function onHandle(ServerRequestInterface $request): void
            {
                $this->request = $request;
            }

            protected function onResponse(ResponseInterface $response): void
            {
                $this->response = $response;
            }
        };

        $request = new FakeServerRequest();
        $response = $handler->handle($request);

        $this->assertSame($request, $handler->request);
        $this->assertSame($response, $handler->response);
    }
}