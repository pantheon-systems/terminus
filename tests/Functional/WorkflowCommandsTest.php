<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\UnitTests\TerminusTestCase;

/**
 * Class WorkflowCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class WorkflowCommandsTest extends TerminusTestCase
{

    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Workflow\WatchCommand
     * @covers \Pantheon\Terminus\Commands\Workflow\ListCommand
     * @group short
     * @group workflow
     *
     */
    public function testWorkflowCRUD()
    {
            $this->fail("Figure out how to test.");
    }
}
