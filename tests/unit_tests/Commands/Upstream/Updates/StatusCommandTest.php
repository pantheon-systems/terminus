<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream\Updates;

use Pantheon\Terminus\Commands\Upstream\Updates\StatusCommand;

/**
 * Class StatusCommandTest
 * Testing class for Pantheon\Terminus\Commands\Upstream\Updates\StatusCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream\Updates
 */
class StatusCommandTest extends UpdatesCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new StatusCommand($this->getConfig());
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the StatusCommand::status($site_env) function
     */
    public function testStatus()
    {
        $status = 'some status';

        $this->upstream_status->expects($this->once())
            ->method('getStatus')
            ->with()
            ->willReturn($status);

        $out = $this->command->status('site.env');
        $this->assertEquals($status, $out);
    }
}
