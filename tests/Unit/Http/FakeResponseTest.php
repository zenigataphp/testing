<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use function fopen;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\StreamInterface;
use Zenigata\Testing\Http\FakeResponse;
use Zenigata\Testing\Http\FakeStream;

/**
 * Unit test for {@see FakeResponse}.
 *
 * Verifies the behavior of the fake PSR-7 response implementation.
 * 
 * Covered cases:
 *
 * - Default status code and reason phrase.
 * - Immutability when changing status code and reason phrase via {@see FakeResponse::withStatus()}.
 * - Ensure the reason phrase associated with the HTTP status code, when left empty.
 * - Ensure headers, body, and protocol version are preserved when changing status.
 */
#[CoversClass(FakeResponse::class)]
final class FakeResponseTest extends TestCase
{
    public function testDefaults(): void
    {
        $response = new FakeResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
    }

    public function testWithStatus(): void
    {
        $original = new FakeResponse();
        $modified = $original->withStatus(418, "I'm a teapot");

        $this->assertNotSame($original, $modified);
        $this->assertSame(200, $original->getStatusCode());
        $this->assertSame(418, $modified->getStatusCode());
        $this->assertSame("I'm a teapot", $modified->getReasonPhrase());
    }

    public function testWithStatusDefaultsEmptyReasonPhrase(): void
    {
        $response = new FakeResponse();
        
        $response = $response->withStatus(201);
        $this->assertSame('Created', $response->getReasonPhrase());

        $response = $response->withStatus(403);
        $this->assertSame('Forbidden', $response->getReasonPhrase());
    }

    public function testWithStatusPreservesHeadersBodyAndProtocol(): void
    {
        $headers = ['X-Custom-Header' => ['1']];
        $body = new FakeStream('foo');

        $original = new FakeResponse(200, 'OK', $headers, $body, '2');
        $modified = $original->withStatus(201);

        $this->assertSame($headers, $modified->getHeaders());
        $this->assertSame($body, $modified->getBody());
        $this->assertSame('2', $modified->getProtocolVersion());
    }

    public function testConstructorAcceptsStringBody(): void
    {
        $request = new FakeResponse(body: 'Hello World');
        $body = $request->getBody();

        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertSame('Hello World', (string) $body);
    }

    public function testConstructorAcceptsResourceBody(): void
    {
        $resource = fopen('data://text/plain,Streamed data', 'r');

        $request  = new FakeResponse(body: $resource);
        $body = $request->getBody();

        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertSame('Streamed data', (string) $body);
    }
}
