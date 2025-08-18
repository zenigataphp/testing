<?php

declare(strict_types=1);

namespace Zenigata\Testing\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Fake implementation of {@see CacheItemPoolInterface} (PSR-6).
 *
 * This fake cache pool behaves like a PSR-6 cache backend, using in-memory storage
 * to simulate cache operations in a testing environment.
 */
class FakeCachePool implements CacheItemPoolInterface
{
    /**
     * Internal storage of cache items, keyed by cache keys.
     * 
     * @var array<string, CacheItemInterface>
     */
    public array $items = [];

    /**
     * Cache items deferred for later persistence.
     * 
     * @var array<string, CacheItemInterface>
     */
    public array $deferred = [];

    /**
     * Retrieves a cache item by its key, creating a new one if none exists.
     *
     * @param string $key The cache key.
     * @return CacheItemInterface The cache item for the given key.
     */
    public function getItem(string $key): CacheItemInterface
    {
        return $this->items[$key] ??= new FakeCacheItem($key);
    }

    /**
     * Retrieves multiple cache items by their keys.
     *
     * @param array<int, string> $keys The list of cache keys.
     * @return iterable<string, CacheItemInterface> Iterable of cache items keyed by cache keys.
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];

        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * Checks if a cache item exists for the specified key.
     *
     * @param string $key The cache key.
     * @return bool True if an item exists for the key, false otherwise.
     */
    public function hasItem(string $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Clears all cache items.
     *
     * @return bool Always returns true.
     */
    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    /**
     * Deletes a cache item by key.
     *
     * @param string $key The cache key to delete.
     * @return bool Always returns true.
     */
    public function deleteItem(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    /**
     * Deletes multiple cache items by their keys.
     *
     * @param array<int, string> $keys The list of cache keys to delete.
     * @return bool Always returns true.
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->items[$key]);
        }

        return true;
    }

    /**
     * Saves a cache item immediately.
     *
     * @param CacheItemInterface $item The cache item to save.
     * @return bool Always returns true.
     */
    public function save(CacheItemInterface $item): bool
    {
        $this->items[$item->getKey()] = $item;
        
        return true;
    }

    /**
     * Saves a cache item for deferred persistence.
     *
     * @param CacheItemInterface $item The cache item to save deferred.
     * @return bool Always returns true.
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * Commits all deferred cache items to the pool.
     *
     * @return bool Always returns true.
     */
    public function commit(): bool
    {
        foreach ($this->deferred as $item) {
            $this->save($item);
        }

        $this->deferred = [];

        return true;
    }
}