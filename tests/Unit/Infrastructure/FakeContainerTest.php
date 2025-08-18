<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Unit\Infrastructure;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Zenigata\Testing\Infrastructure\FakeContainer;

/**
 * Unit test for {@see FakeContainer}.
 *
 * Verifies the behavior of the fake PSR-11 container implementation, including:
 *
 * - Returning stored services when present.
 * - Throwing a {@see NotFoundExceptionInterface} when a service is missing.
 * - Throwing if initialized injecting a non associative array of entries.
 * - Correctly handling `null` as a stored value.
 */
#[CoversClass(FakeContainer::class)]
final class FakeContainerTest extends TestCase
{
    #[Test]
    public function defaults(): void
    {
        $container = new FakeContainer();

        $this->assertEmpty($container->entries);
    }

    #[Test]
    public function returnServiceIfExists(): void
    {
        $container = new FakeContainer(['foo' => 'bar']);

        $this->assertTrue($container->has('foo'));
        $this->assertSame('bar', $container->get('foo'));
    }

    #[Test]
    public function throwIfServiceIsMissing(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage("Service 'missing' not found");

        $container = new FakeContainer();
        $container->get('missing');
    }

    #[Test]
    public function throwIfEntriesIsNotAssociativeArray(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("requires an associative array of entries");

        new FakeContainer(['foo', 'bar']);
    }

    #[Test]
    public function has(): void
    {
        $container = new FakeContainer();

        $this->assertFalse($container->has('service'));

        $container->entries['service'] = 42;

        $this->assertTrue($container->has('service'));
        $this->assertFalse($container->has('missing'));
    }

    #[Test]
    public function nullServiceIsValid(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $container = new FakeContainer(['nullable' => null]);

        $this->assertFalse($container->has('nullable'));

        $container->get('nullable');
    }
}