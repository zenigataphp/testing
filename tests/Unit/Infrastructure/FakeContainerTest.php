<?php

declare(strict_types=1);

namespace Zenigata\Testing\Test\Infrastructure;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Zenigata\Testing\Infrastructure\FakeContainer;

/**
 * Unit test for {@see FakeContainer}.
 *
 * Verifies the behavior of the fake PSR-11 container implementation.
 * 
 * Covered cases:
 *
 * - Return stored services when present.
 * - Throw a {@see NotFoundExceptionInterface} when a service is missing.
 * - Throw if initialized injecting a non associative array of entries.
 * - Correctly handle `null` as a stored value.
 */
#[CoversClass(FakeContainer::class)]
final class FakeContainerTest extends TestCase
{
    public function testDefaults(): void
    {
        $container = new FakeContainer();

        $this->assertEmpty($container->entries);
    }

    public function testReturnServiceIfExists(): void
    {
        $container = new FakeContainer(['foo' => 'bar']);

        $this->assertTrue($container->has('foo'));
        $this->assertSame('bar', $container->get('foo'));
    }

    public function testThrowIfServiceIsMissing(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage("Service 'missing' not found");

        $container = new FakeContainer();
        $container->get('missing');
    }

    public function testThrowIfEntriesIsNotAssociativeArray(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("requires an associative array of entries");

        new FakeContainer(['foo', 'bar']);
    }

    public function testHas(): void
    {
        $container = new FakeContainer();

        $this->assertFalse($container->has('service'));

        $container->entries['service'] = 42;

        $this->assertTrue($container->has('service'));
        $this->assertFalse($container->has('missing'));
    }

    public function testNullServiceIsValid(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $container = new FakeContainer(['nullable' => null]);

        $this->assertFalse($container->has('nullable'));

        $container->get('nullable');
    }
}