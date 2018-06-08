<?php

namespace Pantheon\Terminus\UnitTests\Exceptions;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class TerminusExceptionTest
 * Testing class for Pantheon\Terminus\Exceptions\TerminusException
 * @package Pantheon\Terminus\UnitTests\Exceptions
 */
class TerminusExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the getReplacements function
     */
    public function testGetRawMessage()
    {
        $raw_message = 'raw message';
        $exception = new TerminusException($raw_message);

        $out = $exception->getRawMessage();
        $this->assertEquals($out, $raw_message);
    }

    /**
     * Tests the getReplacements function
     */
    public function testGetReplacements()
    {
        $replacements = ['key' => 'value',];
        $exception = new TerminusException(null, $replacements);

        $out = $exception->getReplacements();
        $this->assertEquals($out, $replacements);
    }

    /**
     * Indirectly tests the interpolateString function
     */
    public function testInterpolateString()
    {
        $raw_message = '{key} is a key';
        $replacements = ['key' => 'value',];
        $expected_message = 'value is a key';
        $exception = new TerminusException($raw_message, $replacements);

        $out = $exception->getMessage();
        $this->assertEquals($out, $expected_message);
    }

    /**
     * Indirectly tests the interpolateString function when the message is an array
     */
    public function testInterpolateStringWithArray()
    {
        $raw_message = ['{key} is a', 'key'];
        $replacements = ['key' => 'value',];
        $expected_message = 'value is a' . PHP_EOL . 'key';
        $exception = new TerminusException($raw_message, $replacements);

        $out = $exception->getMessage();
        $this->assertEquals($out, $expected_message);
    }
}
