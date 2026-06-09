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
     * Create a mock object with optional method stubs.
     *
     * @param string $className The class to mock
     * @param array<string, mixed> $methods Method name => return value pairs
     * @return object The mock object
     */
    protected function createMockWithMethods(string $className, array $methods = []): object
    {
        $mock = $this->createMock($className);

        foreach ($methods as $method => $returnValue) {
            $mock->method($method)->willReturn($returnValue);
        }

        return $mock;
    }

    /**
     * Get a private or protected property value from an object.
     *
     * @param object $object The object
     * @param string $propertyName The property name
     * @return mixed The property value
     */
    protected function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);

        return $property->getValue($object);
    }

    /**
     * Set a private or protected property value on an object.
     *
     * @param object $object The object
     * @param string $propertyName The property name
     * @param mixed $value The value to set
     */
    protected function setPrivateProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setValue($object, $value);
    }

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
