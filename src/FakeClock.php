<?php

declare(strict_types=1);

namespace Zenigata\Testing;

use function is_string;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Fake implementation of {@see ClockInterface} (PSR-20).
 *
 * This fake clock returns a fixed {@see DateTimeImmutable} value. 
 * It is intended for testing purposes, allowing testing 
 * time-dependent logic in a deterministic way.
 */
class FakeClock implements ClockInterface
{
    /**
     * The fixed instant in time that this clock will return.
     *
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $now;

    /**
     * Creates a new fake clock instance.
     *
     * If a {@see DateTimeImmutable} instance or a date/time string is provided, it will be used
     * to initialize the clock's fixed time. Otherwise, the current system time is used.
     *
     * @param DateTimeImmutable|string|null $now Fixed date/time, or null for current system time.
     */
    public function __construct(
        DateTimeImmutable|string|null $now = null,
    ) {
        if (is_string($now)) {
            $this->now = new DateTimeImmutable($now);
        }

        $this->now = match (true) {
            $now instanceof DateTimeImmutable => $now,
            is_string($now)                   => new DateTimeImmutable($now),
            default                           => new DateTimeImmutable()
        };
    }

    /**
     * Returns the fixed time set at construction.
     *
     * @return DateTimeImmutable The fixed time set at construction.
     */
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}