<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\UploadedFileInterface;
use Zenigata\Testing\Http\FakeStream;
use Zenigata\Testing\Http\FakeUploadedFile;

use const UPLOAD_ERR_OK;

/**
 * Unit test for {@see FakeUploadedFile}.
 * 
 * Covered cases:
 *
 * - Default state and capabilities.
 * - Return an explicitly provided stream.
 * - Return explicit size if set, or falling back to the stream size.
 * - Default size to zero when not explicitly provided and the stream is empty.
 * - Return the provided upload error code.
 * - Handle of client filename and media type (both set and null).
 * - No-op behavior of {@see FakeUploadedFile::moveTo()}.
 */
#[CoversClass(FakeUploadedFile::class)]
final class FakeUploadedFileTest extends TestCase
{
    public function testDefaults(): void
    {
        $file = new FakeUploadedFile();

        $this->assertInstanceOf(UploadedFileInterface::class, $file);
        $this->assertInstanceOf(FakeStream::class, $file->getStream());
        $this->assertNull($file->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
        $this->assertNull($file->getClientFilename());
        $this->assertNull($file->getClientMediaType());
    }

    public function testGetStreamReturnsInjectedStream(): void
    {
        $stream = new FakeStream('foo');
        $file = new FakeUploadedFile(stream: $stream);
        
        $this->assertSame($stream, $file->getStream());
    }

    public function testGetSizeReturnsExplicitSizeIfSet(): void
    {
        $file = new FakeUploadedFile(size: 42);

        $this->assertSame(42, $file->getSize());
    }

    public function testGetSizeFallsBackToStreamSize(): void
    {
        $file = new FakeUploadedFile(stream: new FakeStream('abcde'));

        $this->assertSame(5, $file->getSize());
    }

    public function testGetErrorReturnsProvidedErrorCode(): void
    {
        $file = new FakeUploadedFile(error: UPLOAD_ERR_OK);

        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
    }

    public function testSizeDefaultsToZeroIfNotExplicit(): void
    {
        $file = new FakeUploadedFile(stream: new FakeStream());

        $this->assertSame(0, $file->getSize());
    }

    public function testClientFilenameAndMediaTypeAreReturned(): void
    {
        $file = new FakeUploadedFile(
            clientFilename: 'avatar.png',
            clientMediaType: 'image/png'
        );

        $this->assertSame('avatar.png', $file->getClientFilename());
        $this->assertSame('image/png', $file->getClientMediaType());
    }

    public function testClientFilenameAndMediaTypeCanBeNull(): void
    {
        $file = new FakeUploadedFile();

        $this->assertNull($file->getClientFilename());
        $this->assertNull($file->getClientMediaType());
    }

    public function testMoveToDoesNothing(): void
    {
        $this->expectNotToPerformAssertions();
        
        $file = new FakeUploadedFile();
        $file->moveTo('/tmp/target');
    }
}