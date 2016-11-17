<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup\Schedule;

use Pantheon\Terminus\Commands\Backup\Schedule\SetCommand;
use Pantheon\Terminus\UnitTests\Commands\Backup\BackupCommandTest;
use Pantheon\Terminus\Models\Workflow;

/**
 * Testing class for Pantheon\Terminus\Commands\Backup\Schedule\SetCommand
 */
class SetCommandTest extends BackupCommandTest
{
    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new SetCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests the backup:schedule:set command
     */
    public function testSetBackupSchedule()
    {
        $this->environment->id = 'some_env';
        $schedule_info = [
            'hour' => '0',
            'day' => 'Caturday',
        ];

        $this->backups->expects($this->once())
            ->method('setBackupSchedule')
            ->with($this->equalTo($schedule_info))
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Backup schedule successfully set.')
            );

        $out = $this->command->setSchedule('mysite.some_env', $schedule_info);
        $this->assertNull($out);
    }
}
