<?php

declare(strict_types=1);

namespace Zenigata\Testing;

use function array_is_list;
use function sprintf;

use Exception;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Fake implementation of {@see ContainerInterface} (PSR-11).
 *
 * This fake container uses a simple in-memory key-value map 
 * to store and retrieve service entries. It is intended for testing purposes, allowing
 * predictable and isolated behavior without relying on a full dependency injection container.
 */
class FakeContainer implements ContainerInterface
{
    /**
     * In-memory map of service identifiers to their corresponding instances or values.
     * 
     * @var array<string, mixed>
     */
    public array $entries = [];

    /**
     * Creates a new fake container instance.
     *
     * @param array<string, mixed> $entries Associative array mapping service IDs to instances/values.
     * 
     * @throws LogicException If entries are not set as associative array.
     */
    public function __construct(array $entries = [])
    {
        if (!empty($entries) && array_is_list($entries)) {
            throw new LogicException(sprintf(
                "Class '%s' requires an associative array of entries.",
                static::class
            ));
        }

        $this->entries = $entries;
    }

    /**
     * Retrieves a service entry from the container by its identifier.
     *
     * @param string $id Identifier of the service to retrieve.
     * 
     * @return mixed The service instance or value associated with the given ID.
     * @throws NotFoundExceptionInterface If no entry is found for the given ID.
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new class("Service '$id' not found") extends Exception implements NotFoundExceptionInterface {};
        }

        return $this->entries[$id];
    }

    /**
     * Determines whether the container has an entry for the given identifier.
     *
     * @param string $id Identifier of the service to check.
     * 
     * @return bool True if an entry exists for the given ID, false otherwise.
     */
    public function has(string $id): bool
    {
        return isset($this->entries[$id]);
    }
}