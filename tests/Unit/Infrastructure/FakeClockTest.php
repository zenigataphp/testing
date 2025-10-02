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
 * Verifies the behavior of the fake PSR-20 clock implementation.
 * 
 * Covered cases:
 *
 * - Return the current time by default.
 * - Accept a fixed time via {@see DateTimeImmutable} or string.
 * - Return consistent results for the provided fixed time.
 */
#[CoversClass(FakeClock::class)]
final class FakeClockTest extends TestCase
{
    public function testDefaults(): void
    {
        $clock = new FakeClock();

        $this->assertInstanceOf(DateTimeImmutable::class, $clock->now());
        $this->assertSame((new DateTimeImmutable())->getTimestamp(), $clock->now()->getTimestamp());
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