<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Cache;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenigata\Testing\Cache\FakeCacheItem;

/**
 * Unit test for {@see FakeCacheItem}.
 *
 * Verifies the behavior of the fake PSR-6 cache item implementation.
 * 
 * Covered cases:
 *
 * - Return the given key and value via `getKey()` and `get()`.
 * - Report hit/miss status via `isHit()` based on the hit flag and expiration time.
 * - Update value and hit status via `set()`.
 * - Set absolute expiration via `expiresAt()`.
 * - Disable expiration via `expiresAfter(null)`.
 * - Set relative expiration via `expiresAfter()` with seconds or `DateInterval`.
 */
#[CoversClass(FakeCacheItem::class)]
final class FakeCacheItemTest extends TestCase
{
    #[Test]
    public function getKeyReturnsGivenKey(): void
    {
        $item = new FakeCacheItem('foo');

        $this->assertSame('foo', $item->getKey());
    }

    #[Test]
    public function getReturnsGivenValue(): void
    {
        $item = new FakeCacheItem('foo', 'bar');

        $this->assertSame('bar', $item->get());
    }

    #[Test]
    public function isHitReturnsFalseIfFlagIsFalse(): void
    {
        $item = new FakeCacheItem('foo', 'bar', false);

        $this->assertFalse($item->isHit());
    }

    #[Test]
    public function isHitReturnsTrueIfFlagIsTrueAndNoExpiration(): void
    {
        $item = new FakeCacheItem('foo', 'bar', true);

        $this->assertTrue($item->isHit());
    }

    #[Test]
    public function isHitReturnsFalseIfExpired(): void
    {
        $item = new FakeCacheItem('foo', 'bar', true, new DateTimeImmutable('-1 second'));

        $this->assertFalse($item->isHit());
    }

    #[Test]
    public function isHitReturnsTrueIfNotExpired(): void
    {
        $item = new FakeCacheItem('foo', 'bar', true, new DateTimeImmutable('+1 hour'));

        $this->assertTrue($item->isHit());
    }

    #[Test]
    public function setUpdatesValueAndSetsHit(): void
    {
        $item = new FakeCacheItem('foo');

        $item->set('bar');

        $this->assertSame('bar', $item->get());
        $this->assertTrue($item->isHit());
    }

    #[Test]
    public function expiresAtSetsExpiration(): void
    {
        $expiration = new DateTimeImmutable('+10 minutes');
        $item = new FakeCacheItem('foo');

        $item->expiresAt($expiration);
        $item->set('bar'); // We can't assert internal state directly, so we check via isHit()

        $this->assertTrue($item->isHit());
    }

    #[Test]
    public function expiresAfterWithNullDisablesExpiration(): void
    {
        $item = new FakeCacheItem('foo', 'bar', true, new DateTimeImmutable('-1 hour'));

        $item->expiresAfter(null);

        $this->assertTrue($item->isHit());
    }

    #[Test]
    public function expiresAfterWithSeconds(): void
    {
        $item = new FakeCacheItem('foo');

        $item->expiresAfter(3600)->set('bar');

        $this->assertTrue($item->isHit());
    }

    #[Test]
    public function expiresAfterWithDateInterval(): void
    {
        $interval = new DateInterval('PT1H');
        $item = new FakeCacheItem('foo');

        $item->expiresAfter($interval)->set('bar');

        $this->assertTrue($item->isHit());
    }
}
