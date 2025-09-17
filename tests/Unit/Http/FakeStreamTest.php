<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use const SEEK_END;
use const SEEK_SET;

use function fclose;
use function fopen;
use function fwrite;
use function is_resource;
use function rewind;
use function stream_get_contents;

use RuntimeException;
use PHPUnit\Framework\Attributes\CoversClass;
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
 * - Resource-backed streams: read, write, seek, tell, size, and EOF behavior.
 */
#[CoversClass(FakeStream::class)]
final class FakeStreamTest extends TestCase
{
    public function testDefaults(): void
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

    public function testToStringReturnsContent(): void
    {
        $stream = new FakeStream('foo');

        $this->assertSame('foo', (string) $stream);
    }

    public function testGetContentsReturnsRemainingFromPointer(): void
    {
        $stream = new FakeStream('abcdef');
        $stream->read(2);

        $this->assertSame('cdef', $stream->getContents());
    }


    public function testGetSizeReturnsCorrectLength(): void
    {
        $stream = new FakeStream('foo');

        $this->assertSame(3, $stream->getSize());
    }

    public function testGetSizeReturnsResourceSize(): void
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'foobar');
        rewind($resource);

        $stream = new FakeStream($resource);

        $this->assertSame(6, $stream->getSize());

        fclose($resource);
    }

    public function testReadReturnsPortionAndUpdatesPointer(): void
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

    public function testReadFromResourceStream(): void
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'abcdef');
        rewind($resource);

        $stream = new FakeStream($resource);
        $chunk = $stream->read(3);

        $this->assertSame('abc', $chunk);
        $this->assertSame(1, $stream->readCount);
        $this->assertSame(['abc'], $stream->readHistory);

        fclose($resource);
    }

    public function testEofReturnsTrueWhenPointerAtEnd(): void
    {
        $stream = new FakeStream('xyz');
        $stream->read(3);

        $this->assertTrue($stream->eof());
    }

    public function testRewindResetsPointer(): void
    {
        $stream = new FakeStream('12345');
        $stream->read(5);

        $this->assertTrue($stream->eof());

        $stream->rewind();
        
        $this->assertFalse($stream->eof());
        $this->assertSame('12345', $stream->read(5));
    }

    public function testRewindThrowsWhenNotSeekable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream is not seekable.');

        $stream = new FakeStream('foo', seekable: false);
        $stream->rewind();
    }

    public function testWriteAppendsToStream(): void
    {
        $stream = new FakeStream('abc', writable: true);
        $written = $stream->write('def');

        $this->assertSame(3, $written);
        $this->assertSame('abcdef', (string) $stream);
    }

    public function testWriteToResourceStream(): void
    {
        $resource = fopen('php://memory', 'r+');

        $stream = new FakeStream($resource);
        $written = $stream->write('xyz');

        $this->assertSame(3, $written);

        rewind($resource);
        $this->assertSame('xyz', stream_get_contents($resource));

        fclose($resource);
    }

    public function testIsSeekableReadableWritable(): void
    {
        $stream = new FakeStream(seekable: false, readable: false, writable: true);

        $this->assertFalse($stream->isSeekable());
        $this->assertFalse($stream->isReadable());
        $this->assertTrue($stream->isWritable());
    }

    public function testSeekMovesPointer(): void
    {
        $stream = new FakeStream('abcdef');
        $stream->seek(3);

        $this->assertSame(3, $stream->tell());
        $this->assertSame('def', $stream->getContents());

        $stream->seek(-1, SEEK_END);

        $this->assertSame(5, $stream->tell());
        $this->assertSame('f', $stream->getContents());
    }

    public function testSeekTellEofWithResource(): void
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, '12345');
        rewind($resource);

        $stream = new FakeStream($resource);

        $stream->seek(2, SEEK_SET);
        $this->assertSame(2, $stream->tell());

        $stream->seek(-1, SEEK_END);
        $this->assertSame(4, $stream->tell());

        $stream->read(1);
        $this->assertTrue($stream->eof());

        fclose($resource);
    }

    public function testSeekThrowsWhenInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot seek to position');

        $stream = new FakeStream('foo');
        $stream->seek(1000, SEEK_SET); // Out of bounds
    }

    public function testCloseReleasesResource(): void
    {
        $resource = fopen('php://memory', 'r+');

        $stream = new FakeStream($resource);
        $stream->close();

        $this->assertFalse(is_resource($stream->detach()));
    }
}
