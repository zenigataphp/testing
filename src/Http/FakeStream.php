<?php

declare(strict_types=1);

namespace Zenigata\Testing\Http;

use const SEEK_CUR;
use const SEEK_END;
use const SEEK_SET;

use function fclose;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function get_debug_type;
use function is_resource;
use function is_string;
use function rewind;
use function sprintf;
use function stream_get_contents;
use function strlen;
use function substr;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * Fake implementation of {@see StreamInterface} (PSR-7).
 *
 * This fake stream represents a PSR-7 stream for testing purposes.
 * 
 * It simulates reading from and writing to an in-memory string buffer or a PHP stream resource,
 * tracking read operations for inspection, and acting as a test double
 * without relying on real I/O side effects.
 *
 * ⚠️ Limitations with resources:
 * 
 * - Only basic read/write/seek operations are delegated to PHP's stream functions.
 * - Metadata (`uri`, `mode`, etc.) is not fully exposed.
 * - Writes are not synchronized with other consumers of the same resource.
 */
class FakeStream implements StreamInterface
{
    /**
     * Chunks of data read from the stream, recorded in the order they were read.
     *
     * @var string[]
     */
    public array $reads = [];

    /**
     * Creates a new fake stream instance.
     *
     * @param mixed $contents Initial stream content (default: "").
     * @param bool  $seekable Whether the stream supports seeking (default: true).
     * @param bool  $readable Whether the stream supports reading (default: true).
     * @param bool  $writable Whether the stream supports writing (default: true).
     * @param int   $pointer  Initial read/write position (default: 0).
     * 
     * @throws InvalidArgumentException If contents is neither a string nor a resource.
     */
    public function __construct(
        private mixed $contents = '',
        private bool $seekable = true,
        private bool $readable = true,
        private bool $writable = true,
        private int $pointer  = 0
    ) {
        if (!is_string($contents) && !is_resource($contents)) {
            throw new InvalidArgumentException(sprintf(
                "Invalid type for stream contents. Expected one of [string, resource], got '%s'.",
                get_debug_type($contents)
            ));
        }
    }

    /**
     * Returns the entire stream contents as a string.
     *
     * @return string The full contents of the stream.
     */
    public function __toString(): string
    {
        return is_resource($this->contents)
            ? $this->stringifyResource()
            : $this->contents;
    }

    /**
     * Returns the remaining contents from the current pointer to the end.
     *
     * @return string The remaining stream content.
     */
    public function getContents(): string
    {
        return is_resource($this->contents)
            ? stream_get_contents($this->contents)
            : substr($this->contents, $this->pointer);
    }

    /**
     * Returns the size of the stream content.
     *
     * @return int|null Length of the stream contents in bytes, or null if unknown.
     */
    public function getSize(): ?int
    {
        return is_resource($this->contents)
            ? $this->detectResourceSize()
            : strlen($this->contents);
    }

    /**
     * Returns the current position of the read/write pointer.
     *
     * @return int The current offset in the stream.
     */
    public function tell(): int
    {
        return is_resource($this->contents)
            ? ftell($this->contents)
            : $this->pointer;
    }

    /**
     * Checks if the stream pointer is at the end of the contents.
     * 
     * For string-based streams, compares the internal pointer with the string length.
     * For resource streams, uses {@see ftell()} and {@see fstat()} to detect EOF
     * because {@see feof()} only returns true *after* a failed read, not when the
     * pointer is simply positioned at the end.
     *
     * @return bool True if the stream is at EOF, false otherwise.
     */
    public function eof(): bool
    {
        if (is_resource($this->contents)) {
            $pos = ftell($this->contents);
            $stats = fstat($this->contents);

            return $pos === false || $stats === false || $pos >= ($stats['size'] ?? 0);
        }

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
     * Tracks the read operation storing the read chunk.
     *
     * @param int $length Maximum number of bytes to read.
     * 
     * @return string Data read from the stream.
     */
    public function read($length): string
    {
        $chunk = is_resource($this->contents)
            ? $this->readFromResource($length)
            : $this->readFromString($length);

        $this->reads[] = $chunk;

        return $chunk;
    }

    /**
     * Closes the stream and releases its underlying resource, if any.
     *
     * After calling this method the stream becomes unusable: internal buffer or resource is cleared,
     * pointer is reset, readable, writable, and seekable capabilities are disabled.
     *
     * @return void
     */
    public function close(): void
    {
        if (is_resource($this->contents)) {
            fclose($this->contents);
        }

        $this->contents = '';
        $this->pointer = 0;
        $this->readable = false;
        $this->seekable = false;
        $this->writable = false;
    }
    
    /**
     * Detaches the underlying resource.
     *
     * In this fake stream, detaching behaves the same as {@see close()}: 
     * any resource is released, the internal state is cleared, and the stream becomes unusable.
     *
     * @return null Always null, since no usable resource remains attached.
     */
    public function detach()
    {
        $this->close();
        
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

        return is_resource($this->contents)
            ? $this->writeToResource($string)
            : $this->writeToString($string);
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

        if (is_resource($this->contents)) {
            if (fseek($this->contents, $offset, $whence) !== 0) {
                throw new RuntimeException("Failed to seek resource stream.");
            }

            return;
        }

        $length = strlen($this->contents);

        $pointer = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->pointer + $offset,
            SEEK_END => $length + $offset,
            default  => throw new RuntimeException("Invalid whence argument.")
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
            'uri'      => null,
            'mode'     => null,
        ];

        return $key !== null
            ? $meta[$key] ?? null
            : $meta;
    }

    /**
     * Converts a resource stream to a string without altering its position.
     *
     * The resource is rewound, read completely, and then the original position
     * is restored.
     *
     * @return string The full resource contents, or an empty string if nothing can be read.
     */
    private function stringifyResource(): string
    {
        $pos = ftell($this->contents);
        rewind($this->contents);
        $data = stream_get_contents($this->contents);
        fseek($this->contents, $pos);

        return $data ?: '';
    }

    /**
     * Determines the size of the underlying resource, if available.
     *
     * @return int|null The size in bytes, or null if it cannot be detected.
     */
    private function detectResourceSize(): ?int
    {
        $stats = fstat($this->contents);

        return $stats !== false 
            ? $stats['size'] ?? null
            : null;
    }

    /**
     * Reads from a resource stream.
     *
     * @param int $length Maximum number of bytes to read.
     *
     * @return string Data read from the resource, or an empty string on failure/EOF.
     */
    private function readFromResource(int $length): string
    {
        return fread($this->contents, $length) ?: '';
    }

    /**
     * Reads from an internal string buffer and advances the pointer.
     *
     * @param int $length Maximum number of bytes to read.
     *
     * @return string The chunk read from the buffer.
     */
    private function readFromString(int $length): string
    {
        $chunk = substr($this->contents, $this->pointer, $length);
        $this->pointer += strlen($chunk);

        return $chunk;
    }

    /**
     * Writes data to a resource stream.
     *
     * @param string $string Data to write.
     *
     * @return int The number of bytes written.
     */
    private function writeToResource(string $string): int
    {
        return fwrite($this->contents, $string);
    }

    /**
     * Writes data to the internal string buffer.
     *
     * Appends the string, advances the pointer, and returns the number of bytes written.
     *
     * @param string $string Data to write.
     *
     * @return int The number of bytes written.
     */
    private function writeToString(string $string): int
    {
        $this->contents .= $string;
        $written = strlen($string);
        $this->pointer += $written;

        return $written;
    }
}
