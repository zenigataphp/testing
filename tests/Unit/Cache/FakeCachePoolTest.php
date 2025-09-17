<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Zenigata\Testing\Cache\FakeCacheItem;
use Zenigata\Testing\Cache\FakeCachePool;

/**
 * Unit test for {@see FakeCachePool}.
 *
 * Verifies the behavior of the fake PSR-6 cache pool implementation.
 * 
 * Covered cases:
 *
 * - Default state with no stored or deferred items.
 * - Create and returning new items via `getItem()` when missing.
 * - Store and retrieving items via `save()`.
 * - Check item existence with `hasItem()`.
 * - Clear all stored items with `clear()`.
 * - Delete single or multiple items via `deleteItem()` and `deleteItems()`.
 * - Return multiple items via `getItems()`, including missing keys.
 * - Defer items with `saveDeferred()` and moving them to storage via `commit()`.
 */
#[CoversClass(FakeCachePool::class)]
final class FakeCachePoolTest extends TestCase
{
    public function testDefaults(): void
    {
        $cache = new FakeCachePool();

        $this->assertEmpty($cache->items);
        $this->assertEmpty($cache->deferred);
    }

    public function testGetItemCreatesIfMissing(): void
    {
        $cache = new FakeCachePool();

        $item = $cache->getItem('foo');

        $this->assertInstanceOf(FakeCacheItem::class, $item);
        $this->assertSame('foo', $item->getKey());
    }

    public function testSaveStoresItem(): void
    {
        $cache = new FakeCachePool();
        $item = new FakeCacheItem('foo', 'bar', true);

        $cache->save($item);

        $this->assertSame($item, $cache->getItem('foo'));
    }

    public function testSaveDeferredAndCommit(): void
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

    public function testHasItem(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('a'));
        
        $this->assertTrue($cache->hasItem('a'));
        $this->assertFalse($cache->hasItem('b'));
    }

    public function testGetItems(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('a'));
        $cache->save(new FakeCacheItem('b'));

        $items = $cache->getItems(['a', 'b', 'c']);

        $this->assertArrayHasKey('a', $items);
        $this->assertArrayHasKey('b', $items);
        $this->assertArrayHasKey('c', $items);
    }

    public function testClearRemovesAll(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('a'));
        $cache->save(new FakeCacheItem('b'));
        $cache->clear();

        $this->assertFalse($cache->hasItem('a'));
        $this->assertFalse($cache->hasItem('b'));
    }

    public function testDeleteItem(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('foo'));
        $cache->deleteItem('foo');

        $this->assertFalse($cache->hasItem('foo'));
    }

    public function testDeleteItems(): void
    {
        $cache = new FakeCachePool();

        $cache->save(new FakeCacheItem('a'));
        $cache->save(new FakeCacheItem('b'));
        $cache->deleteItems(['a', 'b']);

        $this->assertFalse($cache->hasItem('a'));
        $this->assertFalse($cache->hasItem('b'));
    }
}