<?php
namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Workflow;
use Terminus\Collections\Workflows;

/**
 * Base testing class for Pantheon\Terminus\Commands\Workflow
 */
abstract class WorkflowCommandTest extends CommandTestCase
{
    protected $workflow;

    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
