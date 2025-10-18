<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\StreamInterface;
use Zenigata\Testing\Http\FakeRequest;
use Zenigata\Testing\Http\FakeUri;

use function fopen;

/**
 * Unit test for {@see FakeRequest}.
 * 
 * Covered cases:
 *
 * - Default state.
 * - Immutability when updating values.
 * - Change the request target.
 * - Update the URI and automatically setting the Host header.
 * - Preserve the original Host header when requested.
 * - Stream automatically created from string or resource.
 */
#[CoversClass(FakeRequest::class)]
final class FakeRequestTest extends TestCase
{
    public function testDefaults(): void
    {
        $request = new FakeRequest();

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/', $request->getRequestTarget());
        $this->assertInstanceOf(FakeUri::class, $request->getUri());
    }

    public function testWithMethodIsImmutable(): void
    {
        $original = new FakeRequest(method: 'GET');
        $modified = $original->withMethod('PUT');

        $this->assertNotSame($original, $modified);
        $this->assertSame('GET', $original->getMethod());
        $this->assertSame('PUT', $modified->getMethod());
    }

    public function testWithRequestTarget(): void
    {
        $original = new FakeRequest(requestTarget: '/original');
        $modified = $original->withRequestTarget('/modified');

        $this->assertSame('/original', $original->getRequestTarget());
        $this->assertSame('/modified', $modified->getRequestTarget());
    }

    public function testWithUriSetsHostHeaderByDefault(): void
    {
        $uri = new FakeUri(host: 'example.com');

        $original = new FakeRequest();
        $modified = $original->withUri($uri);

        $this->assertSame(['example.com'], $modified->getHeader('Host'));
    }

    public function testWithUriPreservesHostIfFlagIsTrue(): void
    {
        $uri = new FakeUri(host: 'example.com');

        $original = (new FakeRequest())->withHeader('Host', 'original.com');
        $preserved = $original->withUri($uri, preserveHost: true);

        $this->assertSame(['original.com'], $preserved->getHeader('Host'));
    }

    public function testConstructorAcceptsStringBody(): void
    {
        $request = new FakeRequest(body: 'Hello World');
        $body = $request->getBody();

        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertSame('Hello World', (string) $body);
    }

    public function testConstructorAcceptsResourceBody(): void
    {
        $resource = fopen('data://text/plain,Streamed data', 'r');

        $request  = new FakeRequest(body: $resource);
        $body = $request->getBody();

        $this->assertInstanceOf(StreamInterface::class, $body);
        $this->assertSame('Streamed data', (string) $body);
    }
}
