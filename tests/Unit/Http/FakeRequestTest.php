<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Zenigata\Testing\Http\FakeRequest;
use Zenigata\Testing\Http\FakeUri;

/**
 * Unit test for {@see FakeRequest}.
 *
 * Verifies the behavior of the fake PSR-7 request implementation.
 * 
 * Covered cases:
 *
 * - Default method, request target, and URI instance.
 * - Immutability when changing the HTTP method.
 * - Change the request target via {@see FakeRequest::withRequestTarget()}.
 * - Update the URI and automatically setting the Host header.
 * - Preserve the original Host header when requested.
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

        $original = new FakeRequest()->withHeader('Host', 'original.com');
        $preserved = $original->withUri($uri, preserveHost: true);

        $this->assertSame(['original.com'], $preserved->getHeader('Host'));
    }
}
