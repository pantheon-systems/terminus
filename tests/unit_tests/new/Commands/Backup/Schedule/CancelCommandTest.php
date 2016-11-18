<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup\Schedule;

use Pantheon\Terminus\Commands\Backup\Schedule\CancelCommand;
use Pantheon\Terminus\UnitTests\Commands\Backup\BackupCommandTest;

/**
 * Class CancelCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\Schedule\CancelCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup\Schedule
 */
class CancelCommandTest extends BackupCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new CancelCommand($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the backup:schedule:cancel command
     */
    public function testCancelBackupSchedule()
    {
        $this->environment->id = 'scheduled';

        $this->backups->expects($this->once())
            ->method('cancelBackupSchedule')
            ->with();

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Backup schedule successfully canceled.')
            );

        $out = $this->command->cancelSchedule('mysite.scheduled');
        $this->assertNull($out);
    }
}
