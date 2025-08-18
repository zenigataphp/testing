<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenigata\Testing\Cache\FakeCacheItem;
use Zenigata\Testing\Cache\FakeCacheItemPool;

/**
 * Unit test for {@see FakeCacheItemPool}.
 *
 * Verifies the behavior of the fake PSR-6 cache item pool implementation, including:
 *
 * - Default state with empty `items` and `deferred` collections.
 * - Returning existing items when present in the pool.
 * - Creating new items when requested keys are missing.
 * - Retrieving multiple items at once via `getItems()`.
 * - Correctly reflecting hit/miss status via `hasItem()`.
 * - Clearing all stored items with `clear()`.
 * - Removing single or multiple items via `deleteItem()` and `deleteItems()`.
 * - Deferring items for later storage via `saveDeferred()`.
 * - Committing deferred items into the main pool with `commit()`.
 */
#[CoversClass(FakeCacheItemPool::class)]
final class FakeCacheItemPoolTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $cache = new FakeCacheItemPool();

        $this->assertEmpty($cache->items);
        $this->assertEmpty($cache->deferred);
    }

    #[Test]
    public function getItemReturnsExistingItem(): void
    {
        $pool = new FakeCacheItemPool();
        $item = new FakeCacheItem('foo', 'bar', true);

        $pool->save($item);

        $this->assertSame($item, $pool->getItem('foo'));
        $this->assertSame($item, $pool->items['foo']);
    }

    #[Test]
    public function getItemReturnsNewItemIfMissing(): void
    {
        $pool = new FakeCacheItemPool();

        $item = $pool->getItem('missing');

        $this->assertInstanceOf(FakeCacheItem::class, $item);
        $this->assertSame('missing', $item->getKey());
    }

    #[Test]
    public function getItemsReturnsIterable(): void
    {
        $pool = new FakeCacheItemPool();

        $items = $pool->getItems(['a', 'b']);

        $this->assertCount(2, $items);
        $this->assertArrayHasKey('a', $items);
        $this->assertArrayHasKey('b', $items);
    }

    #[Test]
    public function hasItemReflectsHitStatus(): void
    {
        $pool = new FakeCacheItemPool();

        $pool->save(new FakeCacheItem('found', 42, true));

        $this->assertTrue($pool->hasItem('found'));
        $this->assertFalse($pool->hasItem('missing'));
    }

    #[Test]
    public function clearRemovesAllItems(): void
    {
        $pool = new FakeCacheItemPool();

        $pool->save(new FakeCacheItem('foo'));
        $pool->clear();

        $this->assertEmpty($pool->items);
    }

    #[Test]
    public function deleteItemRemovesOneItem(): void
    {
        $pool = new FakeCacheItemPool();

        $pool->save(new FakeCacheItem('foo'));
        $pool->deleteItem('foo');

        $this->assertArrayNotHasKey('foo', $pool->items);
    }

    #[Test]
    public function deleteItemsRemovesManyItems(): void
    {
        $pool = new FakeCacheItemPool();

        $pool->save(new FakeCacheItem('a'));
        $pool->save(new FakeCacheItem('b'));
        $pool->deleteItems(['a', 'b']);

        $this->assertEmpty($pool->items);
    }

    #[Test]
    public function saveDeferredStoresInDeferred(): void
    {
        $pool = new FakeCacheItemPool();
        $item = new FakeCacheItem('foo', 'bar');

        $pool->saveDeferred($item);

        $this->assertSame($item, $pool->deferred['foo']);
    }

    #[Test]
    public function commitMovesDeferredToItems(): void
    {
        $pool = new FakeCacheItemPool();
        $item = new FakeCacheItem('foo', 'bar');
        
        $pool->saveDeferred($item);
        $pool->commit();

        $this->assertArrayHasKey('foo', $pool->items);
        $this->assertEmpty($pool->deferred);
    }
}
