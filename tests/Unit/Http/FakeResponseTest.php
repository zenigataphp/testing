<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
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
 * - Override the reason phrase explicitly.
 * - Allow an empty reason phrase.
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
        $modified = $original->withStatus(404, 'Not Found');

        $this->assertNotSame($original, $modified);
        $this->assertSame(200, $original->getStatusCode());
        $this->assertSame(404, $modified->getStatusCode());
    }

    public function testWithStatusOverridesReasonPhrase(): void
    {
        $response = new FakeResponse();
        $response = $response->withStatus(404, 'Not Found');

        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testWithStatusAcceptsEmptyReasonPhrase(): void
    {
        $response = new FakeResponse(403, 'Forbidden');
        $response = $response->withStatus(403, '');

        $this->assertSame('', $response->getReasonPhrase());
    }

    public function testWithStatusPreservesHeadersBodyAndProtocol(): void
    {
        $headers = ['X-Custom-Header' => ['1']];
        $body = new FakeStream('foo');

        $original = new FakeResponse(200, 'OK', $headers, $body, '2');
        $modified = $original->withStatus(201, 'Created');

        $this->assertSame($headers, $modified->getHeaders());
        $this->assertSame($body, $modified->getBody());
        $this->assertSame('2', $modified->getProtocolVersion());
    }
}
