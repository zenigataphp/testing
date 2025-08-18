<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenigata\Testing\Cache\FakeCacheItem;
use Zenigata\Testing\Cache\FakeCachePool;

/**
 * Unit test for {@see FakeCachePool}.
 *
 * Verifies the behavior of the fake PSR-6 cache pool implementation, including:
 *
 * - Default state with no stored or deferred items.
 * - Creating and returning new items via `getItem()` when missing.
 * - Storing and retrieving items via `save()`.
 * - Checking item existence with `hasItem()`.
 * - Clearing all stored items with `clear()`.
 * - Deleting single or multiple items via `deleteItem()` and `deleteItems()`.
 * - Returning multiple items via `getItems()`, including missing keys.
 * - Deferring items with `saveDeferred()` and moving them to storage via `commit()`.
 */
#[CoversClass(FakeCachePool::class)]
final class FakeCachePoolTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $cache = new FakeCachePool();

        $this->assertEmpty($cache->items);
        $this->assertEmpty($cache->deferred);
    }

    #[Test]
    public function getItemCreatesIfMissing(): void
    {
        $cache = new FakeCachePool();

        $item = $cache->getItem('foo');

        $this->assertInstanceOf(FakeCacheItem::class, $item);
        $this->assertSame('foo', $item->getKey());
    }

    #[Test]
    public function saveStoresItem(): void
    {
        $cache = new FakeCachePool();
        $item = new FakeCacheItem('foo', 'bar', true);

        $cache->save($item);

        $this->assertSame($item, $cache->getItem('foo'));
    }

    #[Test]
    public function hasItem(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('a'));
        
        $this->assertTrue($cache->hasItem('a'));
        $this->assertFalse($cache->hasItem('b'));
    }

    #[Test]
    public function clearRemovesAll(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('a'));
        $cache->save(new FakeCacheItem('b'));
        $cache->clear();

        $this->assertFalse($cache->hasItem('a'));
        $this->assertFalse($cache->hasItem('b'));
    }

    #[Test]
    public function deleteItem(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('foo'));
        $cache->deleteItem('foo');

        $this->assertFalse($cache->hasItem('foo'));
    }

    #[Test]
    public function deleteItems(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('a'));
        $cache->save(new FakeCacheItem('b'));
        $cache->deleteItems(['a', 'b']);

        $this->assertFalse($cache->hasItem('a'));
        $this->assertFalse($cache->hasItem('b'));
    }

    #[Test]
    public function getItems(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('a'));
        $cache->save(new FakeCacheItem('b'));

        $items = $cache->getItems(['a', 'b', 'c']);

        $this->assertArrayHasKey('a', $items);
        $this->assertArrayHasKey('b', $items);
        $this->assertArrayHasKey('c', $items);
    }

    #[Test]
    public function saveDeferredAndCommit(): void
    {
        $cache = new FakeCachePool();
        $item = new FakeCacheItem('deferred', 42);

        $cache->saveDeferred($item);

        $this->assertArrayHasKey('deferred', $cache->deferred);
        $this->assertFalse($cache->hasItem('deferred'));

        $cache->commit();

        $this->assertEmpty($cache->deferred);
        $this->assertTrue($cache->hasItem('deferred'));
    }
}