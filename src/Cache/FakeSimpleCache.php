<?php

declare(strict_types=1);

namespace Zenigata\Testing\Cache;

use function array_key_exists;
use function is_int;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * Fake implementation of {@see CacheInterface} (PSR-16).
 *
 * This fake simple cache provides an in-memory key-value storage simulation for testing purposes.
 * It supports all standard PSR-16 operations, including optional TTL-based expiration.
 */
class FakeSimpleCache implements CacheInterface
{
    /**
     * Internal cache storage.
     * 
     * Each entry is stored as an array of two elements:
     * 
     *  - mixed               The cached value.
     *  - ?DateTimeImmutable  The expiration timestamp, or null for no expiration.
     *
     * @var array<string, array{mixed, ?DateTimeImmutable}>
     */
    public array $items = [];

    /**
     * Retrieves a value from the cache.
     *
     * @param string $key     The cache key.
     * @param mixed  $default Default value to return if the key is not found or expired.
     * 
     * @return mixed The cached value, or $default if not found.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->items)) {
            return $default;
        }

        [$value, $expiration] = $this->items[$key];

        if ($expiration !== null && $expiration < new DateTimeImmutable()) {
            unset($this->items[$key]);
            return $default;
        }

        return $value;
    }

    /**
     * Stores a value in the cache with optional TTL.
     *
     * @param string                $key   The cache key.
     * @param mixed                 $value The value to store.
     * @param null|int|DateInterval $ttl   Optional TTL in seconds or as DateInterval.
     * 
     * @return bool Always returns true.
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $expiration = $this->normalizeTtl($ttl);
        $this->items[$key] = [$value, $expiration];

        return true;
    }

    /**
     * Deletes a value from the cache.
     *
     * @param string $key The cache key.
     * 
     * @return bool Always returns true.
     */
    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    /**
     * Clears all items from the cache.
     *
     * @return bool Always returns true.
     */
    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    /**
     * Retrieves multiple values from the cache.
     *
     * @param iterable<string> $keys    The cache keys.
     * @param mixed            $default Default value for missing or expired keys.
     * 
     * @return iterable<string, mixed> Associative array of key-value pairs.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * Stores multiple values in the cache.
     *
     * @param iterable<string, mixed> $values Key-value pairs to store.
     * @param null|int|DateInterval   $ttl    Optional TTL in seconds or as DateInterval.
     * 
     * @return bool Always returns true.
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * Deletes multiple values from the cache.
     *
     * @param iterable<string> $keys The cache keys to delete.
     * 
     * @return bool Always returns true.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * Checks if a cache key exists and is not expired.
     *
     * @param string $key The cache key.
     * 
     * @return bool True if the key exists and is valid, false otherwise.
     */
    public function has(string $key): bool
    {
        if (!array_key_exists($key, $this->items)) {
            return false;
        }

        [, $expiration] = $this->items[$key];

        if ($expiration !== null && $expiration < new DateTimeImmutable()) {
            unset($this->items[$key]);
            return false;
        }

        return true;
    }

    /**
     * Converts a TTL value to an absolute expiration date.
     *
     * @param DateInterval|int|null $ttl TTL in seconds, DateInterval, or null.
     * 
     * @return DateTimeImmutable|null The expiration timestamp, or null for no expiration.
     * @throws InvalidArgumentException If the TTL value is invalid.
     */
    private function normalizeTtl(DateInterval|int|null $ttl): ?DateTimeImmutable
    {
        if ($ttl === null) {
            return null;
        }

        $now = new DateTimeImmutable();

        if ($ttl instanceof DateInterval) {
            return $now->add($ttl);
        }

        if (is_int($ttl)) {
            return $now->modify("+{$ttl} seconds");
        }

        throw new InvalidArgumentException('Invalid TTL');
    }
}
