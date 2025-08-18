<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Zenigata\Testing\Http\FakeMessage;
use Zenigata\Testing\Http\FakeStream;

/**
 * Unit test for {@see FakeMessage}.
 *
 * Validates the behavior of the fake PSR-7 message implementation, covering:
 *
 * - Default state, including empty headers, default body, and protocol version.
 * - Header access, retrieval, and string concatenation.
 * - Header manipulation via addition, replacement, and removal.
 * - Immutability when replacing the body or protocol version.
 * - Validation errors when header values are not arrays or contain non-string elements.
 */
#[CoversClass(FakeMessage::class)]
final class FakeMessageTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $message = new FakeMessage();

        $this->assertEmpty($message->getHeaders());
        $this->assertInstanceOf(StreamInterface::class, $message->getBody());
        $this->assertInstanceOf(FakeStream::class, $message->getBody());
        $this->assertSame('1.1', $message->getProtocolVersion());
    }

    #[Test]
    public function headerAccess(): void
    {
        $message = new FakeMessage(headers: ['X-Custom-Header' => ['hello']]);

        $this->assertTrue($message->hasHeader('X-Custom-Header'));
        $this->assertSame(['hello'], $message->getHeader('X-Custom-Header'));
        $this->assertSame('hello', $message->getHeaderLine('X-Custom-Header'));
    }

    #[Test]
    public function headerManipulation(): void
    {
        $message = new FakeMessage(headers: ['X-Custom-Header' => ['abc']]);

        $added = $message->withAddedHeader('X-Custom-Header', 'def');
        $replaced = $message->withHeader('X-Custom-Header', 'xyz');
        $removed = $message->withoutHeader('X-Custom-Header');

        $this->assertSame(['abc', 'def'], $added->getHeader('X-Custom-Header'));
        $this->assertSame(['xyz'], $replaced->getHeader('X-Custom-Header'));
        $this->assertFalse($removed->hasHeader('X-Custom-Header'));
    }

    #[Test]
    public function withBody(): void
    {
        $stream = new FakeStream();

        $original = new FakeMessage();
        $modified = $original->withBody($stream);

        $this->assertNotSame($original, $modified);
        $this->assertSame($stream, $modified->getBody());
    }

    #[Test]
    public function withProtocolVersion(): void
    {
        $original = new FakeMessage();
        $modified = $original->withProtocolVersion('2');

        $this->assertNotSame($original, $modified);
        $this->assertSame('1.1', $original->getProtocolVersion());
        $this->assertSame('2', $modified->getProtocolVersion());
    }

    #[Test]
    public function throwIfHeaderValueIsNotArray(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Header 'X-Custom-Header' must be an array of strings");

        new FakeMessage(headers: ['X-Custom-Header' => 'not-an-array']);
    }

    #[Test]
    public function throwIfHeaderValueContainsNonString(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Header 'X-Custom-Header' expects strings, but index 0 has type integer");

        new FakeMessage(headers: ['X-Custom-Header' => [42]]);
    }
}
