<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Cache;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zenigata\Testing\Cache\FakeCacheItem;
use Zenigata\Testing\Cache\FakeCacheItemPool;

/**
 * Unit test for {@see FakeCacheItemPool}.
 *
 * Verifies the behavior of the fake PSR-6 cache item pool implementation.
 * 
 * Covered cases:
 *
 * - Default state with empty `items` and `deferred` collections.
 * - Return existing items when present in the pool.
 * - Create new items when requested keys are missing.
 * - Retrieve multiple items at once via `getItems()`.
 * - Correctly reflect hit/miss status via `hasItem()`.
 * - Clear all stored items with `clear()`.
 * - Remove single or multiple items via `deleteItem()` and `deleteItems()`.
 * - Defer items for later storage via `saveDeferred()`.
 * - Commit deferred items into the main pool with `commit()`.
 */
#[CoversClass(FakeCacheItemPool::class)]
final class FakeCacheItemPoolTest extends TestCase
{
    public function testDefaults(): void
    {
        $cache = new FakeCacheItemPool();

        $this->assertEmpty($cache->items);
        $this->assertEmpty($cache->deferred);
    }

    public function testGetItemReturnsExistingItem(): void
    {
        $pool = new FakeCacheItemPool();
        $item = new FakeCacheItem('foo', 'bar', true);

        $pool->save($item);

        $this->assertSame($item, $pool->getItem('foo'));
        $this->assertSame($item, $pool->items['foo']);
    }

    public function testGetItemReturnsNewItemIfMissing(): void
    {
        $pool = new FakeCacheItemPool();

        $item = $pool->getItem('missing');

        $this->assertInstanceOf(FakeCacheItem::class, $item);
        $this->assertSame('missing', $item->getKey());
    }

    public function testGetItemsReturnsIterable(): void
    {
        $pool = new FakeCacheItemPool();

        $items = $pool->getItems(['a', 'b']);

        $this->assertCount(2, $items);
        $this->assertArrayHasKey('a', $items);
        $this->assertArrayHasKey('b', $items);
    }

    public function testHasItemReflectsHitStatus(): void
    {
        $pool = new FakeCacheItemPool();

        $pool->save(new FakeCacheItem('found', 42, true));

        $this->assertTrue($pool->hasItem('found'));
        $this->assertFalse($pool->hasItem('missing'));
    }

    public function testClearRemovesAllItems(): void
    {
        $pool = new FakeCacheItemPool();

        $pool->save(new FakeCacheItem('foo'));
        $pool->clear();

        $this->assertEmpty($pool->items);
    }

    public function testDeleteItemRemovesOneItem(): void
    {
        $pool = new FakeCacheItemPool();

        $pool->save(new FakeCacheItem('foo'));
        $pool->deleteItem('foo');

        $this->assertArrayNotHasKey('foo', $pool->items);
    }

    public function testDeleteItemsRemovesManyItems(): void
    {
        $pool = new FakeCacheItemPool();

        $pool->save(new FakeCacheItem('a'));
        $pool->save(new FakeCacheItem('b'));
        $pool->deleteItems(['a', 'b']);

        $this->assertEmpty($pool->items);
    }

    public function testSaveDeferredStoresInDeferred(): void
    {
        $pool = new FakeCacheItemPool();
        $item = new FakeCacheItem('foo', 'bar');

        $pool->saveDeferred($item);

        $this->assertSame($item, $pool->deferred['foo']);
    }

    public function testCommitMovesDeferredToItems(): void
    {
        $pool = new FakeCacheItemPool();
        $item = new FakeCacheItem('foo', 'bar');
        
        $pool->saveDeferred($item);
        $pool->commit();

        $this->assertArrayHasKey('foo', $pool->items);
        $this->assertEmpty($pool->deferred);
    }
}
