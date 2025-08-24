<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use const SEEK_CUR;
use const SEEK_END;
use const SEEK_SET;

use function strlen;
use function substr;

use RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * Fake implementation of {@see StreamInterface} (PSR-7).
 *
 * This fake stream represents a PSR-7 stream for testing purposes.
 * It simulates reading from and writing to an in-memory string buffer, tracking read operations
 * for inspection, and acting as a test double without involving actual stream resources.
 */
class FakeStream implements StreamInterface
{
    /**
     * Number of times the stream has been read.
     *
     * @var int
     */
    public int $readCount = 0;

    /**
     * History of string chunks read from the stream.
     *
     * @var string[]
     */
    public array $readHistory = [];

    /**
     * Creates a new fake stream instance.
     *
     * @param string $contents Initial stream content (default: "").
     * @param bool   $seekable Whether the stream supports seeking (default: true).
     * @param bool   $readable Whether the stream supports reading (default: true).
     * @param bool   $writable Whether the stream supports writing (default: true).
     * @param int    $pointer  Initial read/write position (default: 0).
     */
    public function __construct(
        private string $contents = '',
        private bool $seekable = true,
        private bool $readable = true,
        private bool $writable = true,
        private int $pointer = 0
    ) {}

    /**
     * Returns the entire stream contents as a string.
     *
     * @return string The full contents of the stream.
     */
    public function __toString(): string
    {
        return $this->contents;
    }

    /**
     * Returns the remaining contents from the current pointer to the end.
     *
     * @return string The remaining stream content.
     */
    public function getContents(): string
    {
        return substr($this->contents, $this->pointer);
    }

    /**
     * Returns the size of the stream content.
     *
     * @return int|null Length of the stream contents in bytes, or null if unknown.
     */
    public function getSize(): ?int
    {
        return strlen($this->contents);
    }

    /**
     * Returns the current position of the read/write pointer.
     *
     * @return int The current offset in the stream.
     */
    public function tell(): int
    {
        return $this->pointer;
    }

    /**
     * Checks if the stream pointer is at the end of the contents.
     *
     * @return bool True if at end of stream, false otherwise.
     */
    public function eof(): bool
    {
        return $this->pointer >= strlen($this->contents);
    }

    /**
     * Indicates whether the stream supports seeking.
     *
     * @return bool True if seekable, false otherwise.
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * Resets the pointer to the beginning of the stream.
     *
     * @return void
     * @throws RuntimeException If the stream is not seekable.
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Reads up to $length bytes from the stream, advancing the pointer.
     *
     * Tracks the read operation count and stores the read chunk.
     *
     * @param int $length Maximum number of bytes to read.
     * 
     * @return string Data read from the stream.
     */
    public function read($length): string
    {
        $chunk = substr($this->contents, $this->pointer, $length);
        $this->pointer += strlen($chunk);

        $this->readCount++;
        $this->readHistory[] = $chunk;

        return $chunk;
    }

    /**
     * Closes the stream.
     *
     * No-op in this fake implementation.
     * 
     * @return void
     */
    public function close(): void
    {
        return;
    }
    
    /**
     * Detaches the underlying resource.
     *
     * In this fake stream, clears the buffer and disables capabilities.
     *
     * @return null Always returns null, since no real resource is used.
     */
    public function detach()
    {
        $this->contents = '';
        $this->pointer = 0;
        $this->readable = false;
        $this->seekable = false;
        $this->writable = false;

        return null;
    }
    
    /**
     * Writes data to the stream, appending to existing contents.
     *
     * @param string $string Data to write.
     * 
     * @return int The number of bytes written.
     * @throws RuntimeException If the stream is not writable.
     */
    public function write($string): int 
    {
        if (!$this->writable) {
            throw new RuntimeException("Stream is not writable.");
        }

        $this->contents .= $string;
        $written = strlen($string);
        $this->pointer += $written;
        
        return $written;
    }

    /**
     * Checks if the stream supports writing.
     *
     * @return bool True if writable, false otherwise.
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }
    
    /**
     * Checks if the stream supports reading.
     *
     * @return bool True if readable, false otherwise.
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }
    
    /**
     * Seeks to a position in the stream.
     *
     * @param int $offset The stream offset to seek to.
     * @param int $whence Positioning mode; one of SEEK_SET, SEEK_CUR, SEEK_END.
     * 
     * @return void
     * @throws RuntimeException If the stream is not seekable or offset is invalid.
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new RuntimeException("Stream is not seekable.");
        }

        $length = strlen($this->contents);

        $pointer = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->pointer + $offset,
            SEEK_END => $length + $offset,
            default => throw new RuntimeException("Invalid whence argument.")
        };

        if ($pointer < 0 || $pointer > $length) {
            throw new RuntimeException("Cannot seek to position $pointer; out of bounds.");
        }

        $this->pointer = $pointer;
    }
    
    /**
     * Retrieves stream metadata or a specific metadata key.
     *
     * @param string|null $key Optional metadata key.
     * 
     * @return array|string|bool|null Stream metadata, or a single value if $key is provided.
     */
    public function getMetadata($key = null)
    {
        $meta = [
            'readable' => $this->readable,
            'seekable' => $this->seekable,
            'writable' => $this->writable,
            'uri' => null,
            'mode' => null,
        ];

        return $key !== null
            ? $meta[$key] ?? null
            : $meta;
    }
}
