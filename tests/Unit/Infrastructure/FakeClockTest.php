<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Infrastructure;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Zenigata\Testing\Infrastructure\FakeClock;

/**
 * Unit test for {@see FakeClock}.
 * 
 * Covered cases:
 *
 * - Default state returning the current time.
 * - Accept a fixed time via {@see DateTimeImmutable} or string.
 * - Return consistent results for the provided fixed time.
 */
#[CoversClass(FakeClock::class)]
final class FakeClockTest extends TestCase
{
    public function testDefaults(): void
    {
        $clock = new FakeClock();

        $datetime = new DateTimeImmutable();
        $now = $clock->now();

        $this->assertInstanceOf(DateTimeImmutable::class, $now);
        $this->assertSame($datetime->getTimestamp(), $now->getTimestamp());
    }

    public function testAcceptDateTimeImmutable(): void
    {
        $datetime = new DateTimeImmutable('2023-01-01 00:00:00');

        $clock = new FakeClock($datetime);

        $this->assertSame($datetime, $clock->now());
    }

    public function testAcceptStringDate(): void
    {
        $datetime = '2024-05-05 12:00:00';

        $clock = new FakeClock($datetime);

        $this->assertSame($datetime, $clock->now()->format('Y-m-d H:i:s'));
    }
}