<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Cache;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zenigata\Testing\Cache\FakeCacheItem;

/**
 * Unit test for {@see FakeCacheItem}.
 * 
 * Covered cases:
 *
 * - Return the given key and value.
 * - Report hit/miss status based on the hit flag and expiration time.
 * - Update value and hit status.
 * - Set and disable absolute expiration.
 * - Set relative expiration with seconds or `DateInterval`.
 */
#[CoversClass(FakeCacheItem::class)]
final class FakeCacheItemTest extends TestCase
{
    public function testGetKeyReturnsGivenKey(): void
    {
        $item = new FakeCacheItem('foo');

        $this->assertSame('foo', $item->getKey());
    }

    public function testGetReturnsGivenValue(): void
    {
        $item = new FakeCacheItem('foo', 'bar');

        $this->assertSame('bar', $item->get());
    }

    public function testIsHitReturnsFalseIfFlagIsFalse(): void
    {
        $item = new FakeCacheItem('foo', 'bar', false);

        $this->assertFalse($item->isHit());
    }

    public function testIsHitReturnsTrueIfFlagIsTrueAndNoExpiration(): void
    {
        $item = new FakeCacheItem('foo', 'bar', true);

        $this->assertTrue($item->isHit());
    }

    public function testIsHitReturnsFalseIfExpired(): void
    {
        $item = new FakeCacheItem('foo', 'bar', true, new DateTimeImmutable('-1 second'));

        $this->assertFalse($item->isHit());
    }

    public function testIsHitReturnsTrueIfNotExpired(): void
    {
        $item = new FakeCacheItem('foo', 'bar', true, new DateTimeImmutable('+1 hour'));

        $this->assertTrue($item->isHit());
    }

    public function testSetUpdatesValueAndSetsHit(): void
    {
        $item = new FakeCacheItem('foo');

        $item->set('bar');

        $this->assertSame('bar', $item->get());
        $this->assertTrue($item->isHit());
    }

    public function testExpiresAtSetsExpiration(): void
    {
        $expiration = new DateTimeImmutable('+10 minutes');
        $item = new FakeCacheItem('foo');

        $item->expiresAt($expiration);
        $item->set('bar'); // We can't assert internal state directly, so we check via isHit()

        $this->assertTrue($item->isHit());
    }

    public function testExpiresAfterWithNullDisablesExpiration(): void
    {
        $item = new FakeCacheItem('foo', 'bar', true, new DateTimeImmutable('-1 hour'));

        $item->expiresAfter(null);

        $this->assertTrue($item->isHit());
    }

    public function testExpiresAfterWithSeconds(): void
    {
        $item = new FakeCacheItem('foo');

        $item->expiresAfter(3600)->set('bar');

        $this->assertTrue($item->isHit());
    }

    public function testExpiresAfterWithDateInterval(): void
    {
        $interval = new DateInterval('PT1H');
        $item = new FakeCacheItem('foo');

        $item->expiresAfter($interval)->set('bar');

        $this->assertTrue($item->isHit());
    }
}
