<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Base class for all unit tests.
 *
 * Unit tests should:
 * - Run fast (no network, no filesystem I/O when possible)
 * - Test isolated units of code
 * - Mock external dependencies
 * - Not require any platform credentials or configuration
 */
abstract class UnitTestCase extends TestCase
{
    /**
     * Call a private or protected method on an object.
     *
     * @param object $object The object
     * @param string $methodName The method name
     * @param array<mixed> $args The method arguments
     * @return mixed The method return value
     */
    protected function callPrivateMethod(object $object, string $methodName, array $args = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);

        return $method->invokeArgs($object, $args);
    }
}
