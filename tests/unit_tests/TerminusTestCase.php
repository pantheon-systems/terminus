<?php

namespace Pantheon\Terminus\UnitTests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

// Create our own polyfill for createMock if we are running on an old phpunit that does not have it
if (method_exists(\Yoast\PHPUnitPolyfills\TestCases\TestCase::class, 'createMock')) {
    trait CreateMockPolyfill
    {
    }
} else {
    trait CreateMockPolyfill
    {
        public function createMock($c)
        {
            return $this->getMock($c);
        }
    }
}

/**
 * Class TerminusTestCase
 * @package Pantheon\Terminus\UnitTests
 */
abstract class TerminusTestCase extends TestCase
{
    use CreateMockPolyfill;
}
