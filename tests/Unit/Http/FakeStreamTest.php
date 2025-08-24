<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use const SEEK_END;
use const SEEK_SET;

use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Zenigata\Testing\Http\FakeStream;

/**
 * Unit test for {@see FakeStream}.
 *
 * Verifies the behavior of the fake PSR-7 stream implementation.
 * 
 * Covered cases:
 * 
 * - Default state and capabilities (seekable, readable, writable, pointer position, size, EOF, read history).
 * - String cast returns stream contents.
 * - Accurate size calculation.
 * - Read chunks update the pointer, read count, and read history.
 * - Remaining contents retrieved from the current pointer.
 * - EOF correctly detected.
 * - Rewind resets the stream pointer and throws when not seekable.
 * - Write appends data when the stream is writable.
 * - Capability flags reflect seekable, readable, and writable states.
 * - Metadata retrieval supports full array, specific keys, and null for missing keys.
 * - Seek operation moves the pointer correctly (SEEK_SET, SEEK_CUR, SEEK_END).
 * - Seek throws RuntimeException when attempting to move out of bounds.
 */
#[CoversClass(FakeStream::class)]
final class FakeStreamTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $stream = new FakeStream();

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertIsArray($stream->getMetadata());
        $this->assertSame('', $stream->getContents());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertSame(0, $stream->tell());
        $this->assertSame(0, $stream->getSize());
        $this->assertTrue($stream->eof());
        $this->assertSame(0, $stream->readCount);
        $this->assertEmpty($stream->readHistory);
    }

    #[Test]
    public function getMetadata(): void
    {
        $stream = new FakeStream();

        $meta = $stream->getMetadata();

        $this->assertTrue($meta['readable']);
        $this->assertTrue($meta['seekable']);
        $this->assertTrue($meta['writable']);

        $this->assertTrue($stream->getMetadata('readable'));
        $this->assertNull($stream->getMetadata('missing'));
    }

    #[Test]
    public function toStringReturnsContent(): void
    {
        $stream = new FakeStream('foo');

        $this->assertSame('foo', (string) $stream);
    }

    #[Test]
    public function getSizeReturnsCorrectLength(): void
    {
        $stream = new FakeStream('foo');

        $this->assertSame(3, $stream->getSize());
    }

    #[Test]
    public function readReturnsPortionAndUpdatesPointer(): void
    {
        $stream = new FakeStream('abcdef');

        $result1 = $stream->read(3);
        $result2 = $stream->read(3);

        $this->assertSame('abc', $result1);
        $this->assertSame('def', $result2);
        $this->assertSame(2, $stream->readCount);
        $this->assertSame(['abc', 'def'], $stream->readHistory);
        $this->assertTrue($stream->eof());
    }

    #[Test]
    public function getContentsReturnsRemainingFromPointer(): void
    {
        $stream = new FakeStream('abcdef');
        $stream->read(2);

        $this->assertSame('cdef', $stream->getContents());
    }

    #[Test]
    public function eofReturnsTrueWhenPointerAtEnd(): void
    {
        $stream = new FakeStream('xyz');
        $stream->read(3);

        $this->assertTrue($stream->eof());
    }

    #[Test]
    public function rewindResetsPointer(): void
    {
        $stream = new FakeStream('12345');
        $stream->read(5);

        $this->assertTrue($stream->eof());

        $stream->rewind();
        
        $this->assertFalse($stream->eof());
        $this->assertSame('12345', $stream->read(5));
    }

    #[Test]
    public function rewindThrowsWhenNotSeekable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not seekable.');

        $stream = new FakeStream('foo', seekable: false);
        $stream->rewind();
    }

    #[Test]
    public function writeAppendsToStream(): void
    {
        $stream = new FakeStream('abc', writable: true);
        $written = $stream->write('def');

        $this->assertSame(3, $written);
        $this->assertSame('abcdef', (string) $stream);
    }

    #[Test]
    public function isSeekableReadableWritable(): void
    {
        $stream = new FakeStream(seekable: false, readable: false, writable: true);

        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isReadable());
        $this->assertTrue($stream->isWritable());
    }

    #[Test]
    public function seekMovesPointer(): void
    {
        $stream = new FakeStream('abcdef');
        $stream->seek(3);

        $this->assertSame(3, $stream->tell());
        $this->assertSame('def', $stream->getContents());

        $stream->seek(-1, SEEK_END);

        $this->assertSame(5, $stream->tell());
        $this->assertSame('f', $stream->getContents());
    }

    #[Test]
    public function seekThrowsWhenInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot seek to position');

        $stream = new FakeStream('foo');
        $stream->seek(1000, SEEK_SET); // Out of bounds
    }
}
