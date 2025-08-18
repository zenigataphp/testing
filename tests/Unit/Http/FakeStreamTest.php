<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Zenigata\Testing\Http\FakeStream;

/**
 * Unit test for {@see FakeStream}.
 *
 * Verifies the behavior of the fake PSR-7 stream implementation, covering:
 *
 * - Default state and capabilities (seekable, readable, writable, pointer position, size, EOF, read history).
 * - String casting to return stream contents.
 * - Accurate size calculation.
 * - Reading chunks, updating the pointer, read count, and read history.
 * - Retrieving remaining contents from the current pointer.
 * - EOF detection.
 * - Rewinding the stream pointer and throwing when not seekable.
 * - Writing to the stream when writable.
 * - Capability flags for seekable, readable, and writable states.
 * - Metadata always returning `null`.
 * - Seek operation being a no-op by design.
 */
#[CoversClass(FakeStream::class)]
final class FakeStreamTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $stream = new FakeStream();

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame('', $stream->getContents());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertSame(0, $stream->tell());
        $this->assertSame(0, $stream->getSize());
        $this->assertTrue($stream->eof());
        $this->assertSame(0, $stream->readCount);
        $this->assertEmpty($stream->readHistory);
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
    public function metadataAlwaysNull(): void
    {
        $stream = new FakeStream();

        $this->assertNull($stream->getMetadata());
        $this->assertNull($stream->getMetadata('missing'));
    }

    #[Test]
    public function seekDoesNothing(): void
    {
        $stream = new FakeStream('foo');
        $stream->seek(100); // Does nothing by design // TODO è corretto!? si può simulare qualcosa?!

        $this->assertSame(0, $stream->tell());
    }
}
