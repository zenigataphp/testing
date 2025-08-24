<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Infrastructure;

use Stringable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenigata\Testing\Infrastructure\FakeLogger;

/**
 * Unit test for {@see FakeLogger}.
 *
 * Verifies the behavior of the fake PSR-3 logger implementation.
 * 
 * Covered cases:
 *
 * - Log messages at various levels with string and {@see Stringable} inputs.
 * - Format log entries with level, message, and context data.
 * - Handle empty context arrays.
 * - Track logged messages through the output stack.
 */
#[CoversClass(FakeLogger::class)]
final class FakeLoggerTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $logger = new FakeLogger();

        $this->assertEmpty($logger->output);
    }

    #[Test]
    public function logMessageToOutput(): void
    {
        $logger = new FakeLogger();
        $logger->info('info message', ['foo' => 'bar']);

        $this->assertCount(1, $logger->output);
        $this->assertStringContainsString('[INFO]', $logger->output[0]);
        $this->assertStringContainsString('info message', $logger->output[0]);
        $this->assertStringContainsString('"foo":"bar"', $logger->output[0]);
    }

    #[Test]
    public function logMultipleLevels(): void
    {
        $logger = new FakeLogger();
        $logger->debug('debug message');
        $logger->warning('warning message');

        $this->assertStringContainsString('[DEBUG]', $logger->output[0]);
        $this->assertStringContainsString('[WARNING]', $logger->output[1]);
    }

    #[Test]
    public function logStringableObject(): void
    {
        $message = new class() implements Stringable {
            public function __toString(): string
            {
                return 'stringable message';
            }
        };
        
        $logger = new FakeLogger();
        $logger->error($message);

        $this->assertStringContainsString('stringable message', $logger->output[0]);
    }

    #[Test]
    public function logWithEmptyContext(): void
    {
        $logger = new FakeLogger();
        $logger->alert('alert with no context', []);

        $this->assertStringContainsString('[ALERT]', $logger->output[0]);
        $this->assertStringContainsString('[]', $logger->output[0]);
    }
}