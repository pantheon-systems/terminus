<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow\Info;

use Pantheon\Terminus\UnitTests\Commands\Workflow\WorkflowCommandTest;

/**
 * Class InfoCommandTest
 * Base testing class for Pantheon\Terminus\Commands\Workflow\Info
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow\Info
 */
abstract class InfoCommandTest extends WorkflowCommandTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->workflows->expects($this->once())
            ->method('setPaging')
            ->with(false)
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($this->workflows);
    }
}
