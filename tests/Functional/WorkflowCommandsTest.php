<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class WorkflowCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class WorkflowCommandsTest extends TestCase
{

    use LoginHelperTrait;
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Workflow\WatchCommand
     * @covers \Pantheon\Terminus\Commands\Workflow\ListCommand
     *
     * @group workflow
     * @group todo
     */
    public function testWorkflowCRUD()
    {
        $this->fail("Figure out how to test.");
    }
}
