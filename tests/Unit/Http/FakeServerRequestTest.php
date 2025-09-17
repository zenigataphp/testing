<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Zenigata\Testing\Http\FakeServerRequest;

/**
 * Unit test for {@see FakeServerRequest}.
 *
 * Validates the behavior of the fake PSR-7 server request implementation.
 * 
 * Covered cases:
 *
 * - Default values for server parameters, cookies, query parameters, uploaded files,
 *   parsed body, and attributes.
 * - Attribute retrieval with and without default values.
 * - Immutability when adding or removing attributes.
 * - Set and retrieve cookie parameters.
 * - Set and retrieve query parameters.
 * - Set and retrieve uploaded files.
 * - Set and retrieve the parsed body.
 */
#[CoversClass(FakeServerRequest::class)]
final class FakeServerRequestTest extends TestCase
{
    public function testDefaults(): void
    {
        $request = new FakeServerRequest();

        $this->assertEmpty($request->getServerParams());
        $this->assertEmpty($request->getCookieParams());
        $this->assertEmpty($request->getQueryParams());
        $this->assertEmpty($request->getUploadedFiles());
        $this->assertNull($request->getParsedBody());
        $this->assertEmpty($request->getAttributes());
    }

    public function testGetAttribute(): void
    {
        $request = new FakeServerRequest(attributes: ['foo' => 'bar']);
        
        $this->assertSame('bar', $request->getAttribute('foo', 'default'));
        $this->assertSame('default', $request->getAttribute('missing', 'default'));
    }

    public function testWithAndWihoutAttribute(): void
    {
        $request = new FakeServerRequest();

        $added = $request->withAttribute('foo', 'bar');
        $removed = $added->withoutAttribute('foo');

        $this->assertSame('bar', $added->getAttribute('foo'));
        $this->assertNull($request->getAttribute('foo'));
        $this->assertNull($removed->getAttribute('foo'));
    }

    public function testWithCookieParams(): void
    {
        $original = new FakeServerRequest();
        $modified = $original->withCookieParams(['cookie' => 1]);

        $this->assertSame(['cookie' => 1], $modified->getCookieParams());
        $this->assertEmpty($original->getCookieParams());
    }

    public function testWithQueryParams(): void
    {
        $original = new FakeServerRequest();
        $modified = $original->withQueryParams(['query' => 'foo']);

        $this->assertSame(['query' => 'foo'], $modified->getQueryParams());
    }

    public function testWithUploadedFiles(): void
    {
        $original = new FakeServerRequest();
        $modified = $original->withUploadedFiles(['file' => 'fake']);

        $this->assertSame(['file' => 'fake'], $modified->getUploadedFiles());
    }

    public function testWithParsedBody(): void
    {
        $original = new FakeServerRequest();
        $modified = $original->withParsedBody(['data' => 1]);

        $this->assertSame(['data' => 1], $modified->getParsedBody());
    }
}
