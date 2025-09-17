<?php

declare(strict_types=1);

namespace Zenigata\Testing;

use function json_encode;
use function sprintf;
use function strtoupper;

use Stringable;
use Psr\Log\AbstractLogger;

/**
 * Fake implementation of {@see LoggerInterface} (PSR-3).
 *
 * This fake logger collects log entries in-memory rather than
 * sending them to an actual logging backend. It is designed for testing scenarios
 * where predictable and isolated logging behavior is required.
 */
class FakeLogger extends AbstractLogger
{
    /**
     * Stack of collected log entries, stored as formatted strings.
     *
     * @var string[]
     */
    public array $output = [];

    /**
     * Simulates logging a message.
     * 
     * Stores the formatted message to {@see $output} for later inspection in tests.
     *
     * @param mixed             $level   Log level (e.g "info", "error").
     * @param string|Stringable $message The log message.
     * @param array             $context Additional context data for the log entry.
     * 
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->output[] = sprintf(
            "[%s] %s %s",
            strtoupper($level),
            (string) $message,
            json_encode($context)
        );
    }
}