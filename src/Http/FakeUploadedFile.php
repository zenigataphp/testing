<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use const UPLOAD_ERR_OK;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Fake implementation of {@see UploadedFileInterface} (PSR-7).
 *
 * This class simulates a PSR-7 uploaded file without any real file system interaction.
 * It allows inspection of stream, size, error status, client filename, and media type.
 */
class FakeUploadedFile implements UploadedFileInterface
{
    /**
     * Creates a new fake uploaded file instance.
     *
     * @param StreamInterface $stream          Stream representing the uploaded file content.
     * @param int|null        $size            Size of the uploaded file in bytes, or null to infer from stream.
     * @param int             $error           Upload error status code (default: UPLOAD_ERR_OK).
     * @param string|null     $clientFilename  Original client filename, if any.
     * @param string|null     $clientMediaType Media type sent by the client, if any.
     */
    public function __construct(
        private StreamInterface $stream = new FakeStream(),
        private ?int $size = null,
        private int $error = UPLOAD_ERR_OK,
        private ?string $clientFilename = null,
        private ?string $clientMediaType = null,
    ) {}

    /**
     * Returns the stream representing the uploaded file content.
     *
     * @return StreamInterface The stream resource.
     */
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Moves the uploaded file to a new location.
     *
     * Not applicable in this implementation. It does not perform any filesystem operation.
     *
     * @param string $targetPath The destination path to move the file.
     */
    public function moveTo($targetPath): void
    {
        return;
    }

    /**
     * Retrieves the size of the uploaded file in bytes.
     *
     * Returns the explicitly set size or the size of the stream contents, or 0 if unknown.
     *
     * @return int|null File size in bytes, or null if unknown.
     */
    public function getSize(): ?int
    {
        return $this->size ?? $this->stream->getSize() ?? 0;
    }

    /**
     * Retrieves the upload error status code.
     *
     * @return int One of PHP's UPLOAD_ERR_* constants.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Returns the original filename sent by the client, if available.
     *
     * @return string|null The client-provided filename or null if not set.
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * Returns the media type provided by the client, if available.
     *
     * @return string|null The client-provided media type or null if not set.
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
