<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Http;

use const UPLOAD_ERR_OK;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;
use Zenigata\Testing\Http\FakeStream;
use Zenigata\Testing\Http\FakeUploadedFile;

/**
 * Unit test for {@see FakeUploadedFile}.
 *
 * Verifies the behavior of the fake PSR-7 uploaded file implementation, covering:
 *
 * - Default state: injected stream, null size, default error code, null filename and media type.
 * - Returning an explicitly provided stream.
 * - Returning explicit size if set, or falling back to the stream size.
 * - Defaulting size to zero when not explicitly provided and the stream is empty.
 * - Returning the provided upload error code.
 * - Handling of client filename and media type (both set and null).
 * - No-op behavior of {@see FakeUploadedFile::moveTo()}.
 */
#[CoversClass(FakeUploadedFile::class)]
final class FakeUploadedFileTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $file = new FakeUploadedFile();

        $this->assertInstanceOf(UploadedFileInterface::class, $file);
        $this->assertInstanceOf(FakeStream::class, $file->getStream());
        $this->assertNull($file->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
        $this->assertNull($file->getClientFilename());
        $this->assertNull($file->getClientMediaType());
    }

    #[Test]
    public function getStreamReturnsInjectedStream(): void
    {
        $stream = new FakeStream('foo');
        $file = new FakeUploadedFile(stream: $stream);
        
        $this->assertSame($stream, $file->getStream());
    }

    #[Test]
    public function getSizeReturnsExplicitSizeIfSet(): void
    {
        $file = new FakeUploadedFile(size: 42);

        $this->assertSame(42, $file->getSize());
    }

    #[Test]
    public function getSizeFallsBackToStreamSize(): void
    {
        $file = new FakeUploadedFile(stream: new FakeStream('abcde'));

        $this->assertSame(5, $file->getSize());
    }

    #[Test]
    public function sizeDefaultsToZeroIfNotExplicit(): void
    {
        $file = new FakeUploadedFile(stream: new FakeStream());

        $this->assertSame(0, $file->getSize());
    }

    #[Test]
    public function getErrorReturnsProvidedErrorCode(): void
    {
        $file = new FakeUploadedFile(error: UPLOAD_ERR_OK);

        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
    }

    #[Test]
    public function clientFilenameAndMediaTypeAreReturned(): void
    {
        $file = new FakeUploadedFile(
            clientFilename: 'avatar.png',
            clientMediaType: 'image/png'
        );

        $this->assertSame('avatar.png', $file->getClientFilename());
        $this->assertSame('image/png', $file->getClientMediaType());
    }

    #[Test]
    public function clientFilenameAndMediaTypeCanBeNull(): void
    {
        $file = new FakeUploadedFile();

        $this->assertNull($file->getClientFilename());
        $this->assertNull($file->getClientMediaType());
    }

    #[Test]
    public function moveToDoesNothing(): void
    {
        $this->expectNotToPerformAssertions();
        
        $file = new FakeUploadedFile();
        $file->moveTo('/tmp/target');
    }
}