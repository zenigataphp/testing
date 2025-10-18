<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Cache;

use DateInterval;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zenigata\Testing\Cache\FakeSimpleCache;

/**
 * Unit test for {@see FakeSimpleCache}.
 * 
 * Covered cases:
 *
 * - Default state with no stored items.
 * - Retrieve stored values and returning a default when missing.
 * - Store values.
 * - Detect item key presence.
 * - Remove single items and all items.
 * - Retrieve multiple values, including defaults for missing keys.
 * - Store multiple items.
 * - Remove multiple items.
 * - Handle item expiration via TTL.
 */
#[CoversClass(FakeSimpleCache::class)]
final class FakeSimpleCacheTest extends TestCase
{
    public function testDefaults(): void
    {
        $cache = new FakeSimpleCache();

        $this->assertEmpty($cache->items);
    }

    public function testGetReturnsValueIfExists(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('foo', 'bar');

        $this->assertSame('bar', $cache->get('foo'));
    }

    public function testGetReturnsDefaultIfNotExists(): void
    {
        $cache = new FakeSimpleCache();

        $this->assertSame('default', $cache->get('missing', 'default'));
    }

    public function testSetStoresValue(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('foo', 1);

        $this->assertSame(1, $cache->get('foo'));
    }

    public function testHasDetectsPresence(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('exists', 1);

        $this->assertTrue($cache->has('exists'));
        $this->assertFalse($cache->has('missing'));
    }

    public function testDeleteRemovesItem(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('to_delete', 42);
        $cache->delete('to_delete');

        $this->assertFalse($cache->has('to_delete'));
    }

    public function testClearRemovesAll(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->clear();

        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    public function testGetMultiple(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('a', 1);
        $cache->set('b', 2);

        $results = $cache->getMultiple(['a', 'b', 'c'], 3);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $results);
    }

    public function testSetMultiple(): void
    {
        $cache = new FakeSimpleCache();

        $cache->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertSame(1, $cache->get('a'));
        $this->assertSame(2, $cache->get('b'));
    }

    public function testDeleteMultiple(): void
    {
        $cache = new FakeSimpleCache();

        $cache->set('a', 1);
        $cache->set('b', 2);
        $cache->deleteMultiple(['a', 'b']);

        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    public function testTtlExpiration(): void
    {
        $cache = new FakeSimpleCache();
        $cache->set('short', 42, new DateInterval('PT0S'));

        $this->assertFalse($cache->has('short'));
        $this->assertNull($cache->get('short'));
    }
}