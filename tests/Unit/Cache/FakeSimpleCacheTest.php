<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Cache;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenigata\Testing\Cache\FakeSimpleCache;

/**
 * Unit test for {@see FakeSimpleCache}.
 *
 * Verifies the behavior of the fake PSR-16 simple cache implementation, including:
 *
 * - Default state with no stored items.
 * - Retrieving stored values with `get()` and returning a default when missing.
 * - Storing values with `set()`.
 * - Detecting key presence with `has()`.
 * - Removing single items with `delete()` and all items with `clear()`.
 * - Retrieving multiple values with `getMultiple()`, including defaults for missing keys.
 * - Storing multiple values with `setMultiple()`.
 * - Removing multiple items with `deleteMultiple()`.
 * - Handling item expiration via TTL (both `DateInterval` and immediate expiry).
 */
#[CoversClass(FakeSimpleCache::class)]
final class FakeSimpleCacheTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $cache = new FakeSimpleCache();

        $this->assertEmpty($cache->items);
    }

    #[Test]
    public function getReturnsValueIfExists(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('foo', 'bar');

        $this->assertSame('bar', $cache->get('foo'));
    }

    #[Test]
    public function getReturnsDefaultIfNotExists(): void
    {
        $cache = new FakeSimpleCache();

        $this->assertSame('default', $cache->get('missing', 'default'));
    }

    #[Test]
    public function setStoresValue(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('foo', 1);

        $this->assertSame(1, $cache->get('foo'));
    }

    #[Test]
    public function hasDetectsPresence(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('exists', 1);

        $this->assertTrue($cache->has('exists'));
        $this->assertFalse($cache->has('missing'));
    }

    #[Test]
    public function deleteRemovesItem(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('to_delete', 42);
        $cache->delete('to_delete');

        $this->assertFalse($cache->has('to_delete'));
    }

    #[Test]
    public function clearRemovesAll(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->clear();

        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    #[Test]
    public function getMultiple(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('a', 1);
        $cache->set('b', 2);

        $results = $cache->getMultiple(['a', 'b', 'c'], 3);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $results);
    }

    #[Test]
    public function setMultiple(): void
    {
        $cache = new FakeSimpleCache();

        $cache->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertSame(1, $cache->get('a'));
        $this->assertSame(2, $cache->get('b'));
    }

    #[Test]
    public function deleteMultiple(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->deleteMultiple(['a', 'b']);

        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    #[Test]
    public function ttlExpiration(): void
    {
        $cache = new FakeSimpleCache();
        $cache->set('short', 42, new DateInterval('PT0S'));

        $this->assertFalse($cache->has('short'));
        $this->assertNull($cache->get('short'));
    }
}