<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
 * - Correct execution of `onHandle()` and `onResponse()` hooks.
 */
#[CoversClass(FakeRequestHandler::class)]
final class FakeRequestHandlerTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $handler = new FakeRequestHandler();

        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
    }

    #[Test]
    public function returnResponse(): void
    {
        $handler = new FakeRequestHandler();

        $response = $handler->handle(new FakeServerRequest());

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(FakeResponse::class, $response);
    }

    #[Test]
    public function returnCustomResponseIfProvided(): void
    {
        $initialResponse = new FakeResponse();
        $handler = new FakeRequestHandler($initialResponse);

        $response = $handler->handle(new FakeServerRequest());

        $this->assertSame($initialResponse, $response);
    }

    #[Test]
    public function hooksCorrectExecution(): void
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