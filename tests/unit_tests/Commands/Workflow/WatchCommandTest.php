<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Workflow\WatchCommand;

/**
 * Class WatchCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\WatchCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow
 */
class WatchCommandTest extends WorkflowCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new WatchCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:list command
     */
    public function testWatch()
    {
    }
}
