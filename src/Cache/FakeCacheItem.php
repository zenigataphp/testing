<?php

declare(strict_types=1);

namespace Zenigata\Testing\Cache;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

/**
 * Fake implementation of {@see CacheItemInterface} (PSR-6).
 *
 * This fake cache item stores a key, value, hit status, and expiration metadata
 * entirely in memory, making it useful for simulating cache behavior in tests
 * without requiring a real caching system.
 */
class FakeCacheItem implements CacheItemInterface
{
    /**
     * Creates a new fake cache item instance.
     *
     * @param string                 $key        The key for this cache item.
     * @param mixed                  $value      The value stored in the cache item.
     * @param bool                   $hit        Whether the cache item is considered a cache hit (default: false).
     * @param DateTimeInterface|null $expiration Optional expiration time of the cache item (default: null).
     */
    public function __construct(
        private string $key,
        private mixed $value = null,
        private bool $hit = false,
        private ?DateTimeInterface $expiration = null,
    ) {}

    /**
     * Retrieves the key identifying this cache item.
     *
     * @return string The cache item key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Retrieves the value of the cache item.
     *
     * @return mixed The cached value or null if none is set.
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Indicates whether the cache item is a cache hit.
     *
     * Returns false if the hit flag is false or if the item has expired.
     *
     * @return bool True if the item is a hit and not expired, false otherwise.
     */
    public function isHit(): bool
    {
       if (!$this->hit) {
            return false;
        }

        if ($this->expiration === null) {
            return true;
        }

        return $this->expiration > new DateTimeImmutable();
    }

    /**
     * Stores the value represented by this cache item.
     *
     * Marks the item as a cache hit.
     *
     * @param mixed $value The value to store.
     * 
     * @return static Returns self for method chaining.
     */
    public function set(mixed $value): static
    {
        $this->value = $value;
        $this->hit = true;

        return $this;
    }

    /**
     * Stores the expiration time for this cache item.
     *
     * @param DateTimeInterface|null $expiration The expiration time or null for no expiration.
     * 
     * @return static Returns self for method chaining.
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;
        
        return $this;
    }

    /**
     * Stores the expiration time for this cache item relative to the current time.
     *
     * @param int|DateInterval|null $time Time to expiration as seconds, interval, or null for no expiration.
     * 
     * @return static Returns self for method chaining.
     */
    public function expiresAfter(int|DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expiration = null;
        } else {
            $this->expiration = $time instanceof DateInterval
                ? (new DateTimeImmutable())->add($time)
                : (new DateTimeImmutable())->modify("+$time seconds");
        }

        return $this;
    }
}