<?php

declare(strict_types=1);

namespace Zenigata\Testing\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Fake implementation of {@see CacheItemPoolInterface} (PSR-6).
 *
 * This fake cache item pool provides an in-memory simulation of a PSR-6 cache backend,
 * allowing tests to manage cache items without relying on external storage.
 */
class FakeCacheItemPool implements CacheItemPoolInterface
{
    /** 
     * In-memory store of cache items keyed by their cache keys.
     * 
     * @var array<string, CacheItemInterface> 
     */
    public array $items = [];

    /**
     * Deferred cache items waiting to be committed.
     * 
     * @var array<string, CacheItemInterface> 
     */
    public array $deferred = [];

    /**
     * Retrieves a cache item by its key.
     *
     * Returns a new cache item if none exists for the given key.
     *
     * @param string $key The cache key.
     * @return CacheItemInterface The cache item for the specified key.
     */
    public function getItem(string $key): CacheItemInterface
    {
        return $this->items[$key] ?? new FakeCacheItem($key);
    }

    /**
     * Retrieves multiple cache items by their keys.
     *
     * @param array<int, string> $keys List of cache keys to retrieve.
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
     * Checks whether a cache item exists and is a hit for the given key.
     *
     * @param string $key The cache key.
     * @return bool True if the cache item exists and is a hit, false otherwise.
     */
    public function hasItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * Clears all cache items from the pool.
     *
     * @return bool Always returns true.
     */
    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    /**
     * Deletes a cache item by its key.
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
     * @param array<int, string> $keys List of cache keys to delete.
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