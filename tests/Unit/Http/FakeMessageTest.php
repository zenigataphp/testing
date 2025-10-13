<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use function array_filter;
use function array_is_list;
use function is_array;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\StreamInterface;
use Zenigata\Testing\Http\FakeMessage;
use Zenigata\Testing\Http\FakeStream;

/**
 * Unit test for {@see FakeMessage}.
 *
 * Validates the behavior of the fake PSR-7 message implementation.
 * 
 * Covered cases:
 *
 * - Default state, including empty headers, default body, and protocol version.
 * - Header normalization ensures case-insensitive access while preserving original header names.
 * - Header access, retrieval, and string concatenation.
 * - Header manipulation via addition, replacement, and removal.
 * - Immutability when replacing the body or protocol version.
 */
#[CoversClass(FakeMessage::class)]
final class FakeMessageTest extends TestCase
{
    public function testDefaults(): void
    {
        $message = new FakeMessage();

        $this->assertEmpty($message->getHeaders());
        $this->assertInstanceOf(StreamInterface::class, $message->getBody());
        $this->assertInstanceOf(FakeStream::class, $message->getBody());
        $this->assertSame('1.1', $message->getProtocolVersion());
    }

    public function testHeadersNormalization(): void
    {
        $message = new FakeMessage(
            headers: [
                'Content-Type'    => 'application/json',
                'X-CUSTOM-Header' => ['foo', 'bar'],
            ]
        );

        $headers = $message->getHeaders();
        
        // Header names are not changed
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('X-CUSTOM-Header', $headers);

        // Each value must be an array of strings
        foreach ($headers as $header) {
            $this->assertTrue(is_array($header) && array_is_list($header));
            $this->assertTrue(array_filter($header, 'is_string') === $header);
        }

        // Case-insensitive access
        $this->assertSame(['application/json'], $message->getHeader('content-type'));
        $this->assertSame(['foo', 'bar'], $message->getHeader('x-custom-header'));
    }

    public function testHeaderAccess(): void
    {
        $message = new FakeMessage(headers: ['X-Custom-Header' => 'hello']);

        $this->assertTrue($message->hasHeader('X-Custom-Header'));
        $this->assertSame(['hello'], $message->getHeader('X-Custom-Header'));
        $this->assertSame('hello', $message->getHeaderLine('X-Custom-Header'));
    }

    public function testHeaderManipulation(): void
    {
        $message = new FakeMessage(headers: ['X-Custom-Header' => 'abc']);

        $added = $message->withAddedHeader('X-Custom-Header', 'def');
        $replaced = $message->withHeader('X-Custom-Header', 'xyz');
        $removed = $message->withoutHeader('X-Custom-Header');

        $this->assertSame(['abc', 'def'], $added->getHeader('X-Custom-Header'));
        $this->assertSame(['xyz'], $replaced->getHeader('X-Custom-Header'));
        $this->assertFalse($removed->hasHeader('X-Custom-Header'));
    }

    public function testWithBody(): void
    {
        $stream = new FakeStream();

        $original = new FakeMessage();
        $modified = $original->withBody($stream);

        $this->assertNotSame($original, $modified);
        $this->assertSame($stream, $modified->getBody());
    }

    public function testWithProtocolVersion(): void
    {
        $original = new FakeMessage();
        $modified = $original->withProtocolVersion('2');

        $this->assertNotSame($original, $modified);
        $this->assertSame('1.1', $original->getProtocolVersion());
        $this->assertSame('2', $modified->getProtocolVersion());
    }
}
