<?php
/**
 * @file
 *
 * Contains Pantheon\Terminus\UnitTests\Commands\Connection\ConnectionCommandTest
 */

namespace Pantheon\Terminus\UnitTests\Commands\Connection;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Psr\Log\NullLogger;

/**
 * Abstract Class ConnectionCommandTest
 * This may no longer have a raison d'être
 *
 * @package Pantheon\Terminus\UnitTests\Commands\Connection
 */
abstract class ConnectionCommandTest extends CommandTestCase
{
    protected $command;
    protected $logger;

    /**
     * Test Suite Setup
     */
    protected function setUp()
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(array('log'))
            ->getMock();
    }

    /**
     * Test Suite Teardown
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
}
