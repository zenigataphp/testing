<?php

declare(strict_types=1);

namespace Zenigata\Testing\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Fake implementation of {@see NotFoundExceptionInterface} (PSR-11).
*
 * Exception thrown when a requested container entry is not found.
 * 
 * Used by {@see Zenigata\Testing\Infrastructure\FakeContainer} to simulate 
 * missing dependencies or invalid service identifiers during tests.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{}
